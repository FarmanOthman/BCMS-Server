<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $report = DailySalesReport::where('report_date', $request->date)->first();

        if (!$report) {
            return response()->json(['message' => 'No daily report found for this date.'], 404);
        }

        return response()->json($report);
    }
}
