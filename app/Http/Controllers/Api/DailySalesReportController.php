<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailySalesReportController extends Controller
{
    /**
     * Display a listing of the daily sales reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Allow filtering by date range
        $query = DailySalesReport::query();
        
        if ($request->has('from_date')) {
            $query->where('report_date', '>=', $request->input('from_date'));
        }
        
        if ($request->has('to_date')) {
            $query->where('report_date', '<=', $request->input('to_date'));
        }
        
        // Order by date descending (most recent first)
        $reports = $query->orderBy('report_date', 'desc')->get();
        
        return response()->json($reports);
    }

    /**
     * Display the daily sales report for a specific date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $validated = $this->validate($request, [
            'date' => 'required|date_format:Y-m-d',
        ]);

        $report = DailySalesReport::where('report_date', $validated['date'])->first();

        if (!$report) {
            return response()->json(['message' => 'No daily report found for this date.'], 404);
        }

        return response()->json($report);
    }
    
    /**
     * Store a newly created daily sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'report_date' => 'required|date_format:Y-m-d|unique:dailysalesreport,report_date',
            'total_sales' => 'required|integer|min:0',
            'total_revenue' => 'required|numeric|min:0',
            'total_profit' => 'required|numeric',
            'avg_profit_per_sale' => 'required|numeric',
            'most_profitable_car_id' => 'nullable|uuid|exists:cars,id',
            'highest_single_profit' => 'nullable|numeric',
        ]);
        
        try {
            $report = DB::transaction(function () use ($validated) {
                // Set creator/updater info
                $userId = Auth::id();
                $validated['created_by'] = $userId;
                $validated['updated_by'] = $userId;
                
                return DailySalesReport::create($validated);
            });
            
            Log::info('Daily sales report created for date: ' . $validated['report_date']);
            return response()->json($report, 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create daily sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create daily sales report.', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update the specified daily sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $date)
    {
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['message' => 'Invalid date format. Use YYYY-MM-DD.'], 422);
        }
        
        $report = DailySalesReport::where('report_date', $date)->first();
        
        if (!$report) {
            return response()->json(['message' => 'No daily report found for this date.'], 404);
        }
        
        $validated = $this->validate($request, [
            'total_sales' => 'sometimes|required|integer|min:0',
            'total_revenue' => 'sometimes|required|numeric|min:0',
            'total_profit' => 'sometimes|required|numeric',
            'avg_profit_per_sale' => 'sometimes|required|numeric',
            'most_profitable_car_id' => 'nullable|uuid|exists:cars,id',
            'highest_single_profit' => 'nullable|numeric',
        ]);
        
        try {
            DB::transaction(function () use ($report, $validated) {
                // Set updater info
                $validated['updated_by'] = Auth::id();
                
                $report->update($validated);
            });
            
            Log::info('Daily sales report updated for date: ' . $date);
            return response()->json($report->fresh());
            
        } catch (\Exception $e) {
            Log::error('Failed to update daily sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update daily sales report.', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Remove the specified daily sales report.
     *
     * @param  string  $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($date)
    {
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['message' => 'Invalid date format. Use YYYY-MM-DD.'], 422);
        }
        
        $report = DailySalesReport::where('report_date', $date)->first();
        
        if (!$report) {
            return response()->json(['message' => 'No daily report found for this date.'], 404);
        }
        
        try {
            DB::transaction(function () use ($report) {
                $report->delete();
            });
            
            Log::info('Daily sales report deleted for date: ' . $date);
            return response()->json(['message' => 'Daily sales report deleted successfully.'], 200);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete daily sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete daily sales report.', 'error' => $e->getMessage()], 500);
        }
    }
}
