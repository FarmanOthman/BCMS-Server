<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YearlySalesReportController extends Controller
{
    /**
     * Display a listing of yearly sales reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get all yearly reports ordered by year descending (most recent first)
        $reports = YearlySalesReport::orderBy('year', 'desc')->get();
        
        return response()->json($reports);
    }

    /**
     * Display the yearly sales report for a specific year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $validated = $this->validate($request, [
            'year' => 'required|integer|min:1900|max:2100',
        ]);

        $report = YearlySalesReport::find($validated['year']);

        if (!$report) {
            return response()->json(['message' => 'No yearly report found for this year.'], 404);
        }

        return response()->json($report);
    }
    
    /**
     * Store a newly created yearly sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {        $validated = $this->validate($request, [
            'year' => 'required|integer|min:1900|max:2100',
            'total_sales' => 'required|integer|min:0',
            'total_revenue' => 'required|numeric|min:0',
            'total_profit' => 'required|numeric',
            'avg_monthly_profit' => 'required|numeric',
            'best_month' => 'nullable|integer|min:1|max:12',
            'best_month_profit' => 'nullable|numeric',
            'profit_margin' => 'nullable|numeric',
        ]);
        
        // Check for existing report with same year
        $exists = YearlySalesReport::find($validated['year']);
        if ($exists) {
            return response()->json([
                'message' => 'A yearly report already exists for this year.',
                'errors' => ['year' => ['A report for this year already exists.']]
            ], 422);
        }
        
        try {            $report = DB::transaction(function () use ($validated) {
                // Set creator/updater info
                $userId = Auth::id();
                $validated['created_by'] = $userId;
                $validated['updated_by'] = $userId;
                
                return YearlySalesReport::create($validated);
            });
            
            Log::info('Yearly sales report created for ' . $validated['year']);
            return response()->json($report, 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create yearly sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create yearly sales report.', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update the specified yearly sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $year)
    {
        // Validate input parameters
        if (!is_numeric($year) || $year < 1900 || $year > 2100) {
            return response()->json(['message' => 'Invalid year.'], 422);
        }
        
        $report = YearlySalesReport::find($year);
        
        if (!$report) {
            return response()->json(['message' => 'No yearly report found for this year.'], 404);
        }
          $validated = $this->validate($request, [
            'total_sales' => 'sometimes|required|integer|min:0',
            'total_revenue' => 'sometimes|required|numeric|min:0',
            'total_profit' => 'sometimes|required|numeric',
            'avg_monthly_profit' => 'sometimes|required|numeric',
            'best_month' => 'nullable|integer|min:1|max:12',
            'best_month_profit' => 'nullable|numeric',
            'profit_margin' => 'nullable|numeric',
        ]);
        
        try {            DB::transaction(function () use ($report, $validated) {
                // Set updater info
                $validated['updated_by'] = Auth::id();
                $report->update($validated);
            });
            
            Log::info('Yearly sales report updated for ' . $year);
            return response()->json($report->fresh());
            
        } catch (\Exception $e) {
            Log::error('Failed to update yearly sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update yearly sales report.', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Remove the specified yearly sales report.
     *
     * @param  int  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($year)
    {
        // Validate input parameters
        if (!is_numeric($year) || $year < 1900 || $year > 2100) {
            return response()->json(['message' => 'Invalid year.'], 422);
        }
        
        $report = YearlySalesReport::find($year);
        
        if (!$report) {
            return response()->json(['message' => 'No yearly report found for this year.'], 404);
        }
        
        try {
            DB::transaction(function () use ($report) {
                $report->delete();
            });
            
            Log::info('Yearly sales report deleted for ' . $year);
            return response()->json(['message' => 'Yearly sales report deleted successfully.'], 200);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete yearly sales report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete yearly sales report.', 'error' => $e->getMessage()], 500);
        }
    }
    

}
