<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceRecord;
use App\Models\MonthlySalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonthlySalesReportController extends Controller
{
    /**
     * Display a listing of monthly sales reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Allow filtering by year
        $query = MonthlySalesReport::query();
        
        if ($request->has('year')) {
            $query->where('year', $request->input('year'));
        }
        
        // Order by year and month descending (most recent first)
        $reports = $query->orderBy('year', 'desc')
                         ->orderBy('month', 'desc')
                         ->get();
        
        return response()->json($reports);
    }

    /**
     * Display the monthly sales report for a specific year and month.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $validated = $this->validate($request, [
            'year' => 'required|integer|min:1900|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $report = MonthlySalesReport::forYearMonth($validated['year'], $validated['month'])->first();

        if (!$report) {
            return response()->json(['message' => 'No monthly report found for this year and month.'], 404);
        }

        return response()->json($report);
    }
    
    /**
     * Store a newly created monthly sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'year' => 'required|integer|min:1900|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'total_sales' => 'required|integer|min:0',
            'total_revenue' => 'required|numeric|min:0',
            'total_profit' => 'required|numeric',
            'avg_daily_profit' => 'required|numeric',
            'best_day' => 'nullable|date_format:Y-m-d',            
            'best_day_profit' => 'nullable|numeric',
            'profit_margin' => 'nullable|numeric',
            'finance_cost' => 'required|numeric|min:0',
            'total_finance_cost' => 'nullable|numeric|min:0',
            'net_profit' => 'required|numeric',
        ]);
        
        // Check for existing report with same year and month
        $exists = MonthlySalesReport::forYearMonth($validated['year'], $validated['month'])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'A monthly report already exists for this year and month.',
                'errors' => ['year' => ['A report for this year and month already exists.']]
            ], 422);
        }
        
        try {
            $report = DB::transaction(function () use ($validated) {
                // Set creator/updater info
                $userId = Auth::id();
                $validated['created_by'] = $userId;
                $validated['updated_by'] = $userId;
                
                // If total_finance_cost is not provided, calculate it from finance records
                if (!isset($validated['total_finance_cost'])) {
                    $financeRecords = FinanceRecord::whereYear('record_date', $validated['year'])
                                                 ->whereMonth('record_date', $validated['month'])
                                                 ->get();
                    $validated['total_finance_cost'] = round((float)$financeRecords->sum('cost'), 2);
                    
                    // Recalculate net_profit if total_finance_cost was changed
                    $validated['net_profit'] = round((float)($validated['total_profit'] - $validated['total_finance_cost']), 2);
                    
                    Log::info('Calculated total_finance_cost for monthly report: ' . $validated['total_finance_cost']);
                }
                
                return MonthlySalesReport::create($validated);
            });
            
            Log::info('Monthly sales report created for ' . $validated['year'] . '-' . $validated['month']);
            return response()->json($report, 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create monthly sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create monthly sales report.', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update the specified monthly sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $year
     * @param  int  $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $year, $month)
    {
        // Validate input parameters
        if (!is_numeric($year) || !is_numeric($month) || 
            $year < 1900 || $year > 2100 || $month < 1 || $month > 12) {
            return response()->json(['message' => 'Invalid year or month.'], 422);
        }
        
        $report = MonthlySalesReport::forYearMonth($year, $month)->first();
        
        if (!$report) {
            return response()->json(['message' => 'No monthly report found for this year and month.'], 404);
        }
        
        $validated = $this->validate($request, [
            'start_date' => 'sometimes|required|date_format:Y-m-d',
            'end_date' => 'sometimes|required|date_format:Y-m-d|after_or_equal:start_date',
            'total_sales' => 'sometimes|required|integer|min:0',
            'total_revenue' => 'sometimes|required|numeric|min:0',
            'total_profit' => 'sometimes|required|numeric',
            'avg_daily_profit' => 'sometimes|required|numeric',
            'best_day' => 'nullable|date_format:Y-m-d',            
            'best_day_profit' => 'nullable|numeric',
            'profit_margin' => 'nullable|numeric',
            'finance_cost' => 'sometimes|required|numeric|min:0',
            'total_finance_cost' => 'nullable|numeric|min:0',
            'net_profit' => 'sometimes|required|numeric',
        ]);
        
        try {
            DB::transaction(function () use ($report, $validated, $year, $month) {
                // Set updater info
                $validated['updated_by'] = Auth::id();
                
                // If total_finance_cost is not provided, calculate it from finance records
                if (!isset($validated['total_finance_cost'])) {
                    $financeRecords = FinanceRecord::whereYear('record_date', $year)
                                                 ->whereMonth('record_date', $month)
                                                 ->get();
                    $validated['total_finance_cost'] = round((float)$financeRecords->sum('cost'), 2);
                    
                    // If net_profit is not provided, recalculate it
                    if (!isset($validated['net_profit'])) {
                        // Use the new total_profit if provided, otherwise use the existing one
                        $totalProfit = $validated['total_profit'] ?? $report->total_profit;
                        $validated['net_profit'] = round((float)($totalProfit - $validated['total_finance_cost']), 2);
                    }
                    
                    Log::info('Calculated total_finance_cost for monthly report update: ' . $validated['total_finance_cost']);
                }
                
                $report->update($validated);
            });
            
            Log::info('Monthly sales report updated for ' . $year . '-' . $month);
            return response()->json($report->fresh());
            
        } catch (\Exception $e) {
            Log::error('Failed to update monthly sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update monthly sales report.', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Remove the specified monthly sales report.
     *
     * @param  int  $year
     * @param  int  $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($year, $month)
    {
        // Validate input parameters
        if (!is_numeric($year) || !is_numeric($month) || 
            $year < 1900 || $year > 2100 || $month < 1 || $month > 12) {
            return response()->json(['message' => 'Invalid year or month.'], 422);
        }
        
        $report = MonthlySalesReport::forYearMonth($year, $month)->first();
        
        if (!$report) {
            return response()->json(['message' => 'No monthly report found for this year and month.'], 404);
        }
        
        try {
            DB::transaction(function () use ($report) {
                $report->delete();
            });
            
            Log::info('Monthly sales report deleted for ' . $year . '-' . $month);
            return response()->json(['message' => 'Monthly sales report deleted successfully.'], 200);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete monthly sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete monthly sales report.', 'error' => $e->getMessage()], 500);
        }
    }
}
