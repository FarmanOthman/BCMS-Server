<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySalesReport;
use Illuminate\Http\Request;

class DailySalesReportController extends Controller
{
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
}
