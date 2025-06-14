<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlySalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MonthlySalesReportController extends Controller
{
    /**
     * Display the monthly sales report for a specific year and month.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:1900|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $report = MonthlySalesReport::forYearMonth($request->year, $request->month)->first();

        if (!$report) {
            return response()->json(['message' => 'No monthly report found for this year and month.'], 404);
        }

        return response()->json($report);
    }
}
