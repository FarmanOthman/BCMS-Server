<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\YearlySalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class YearlySalesReportController extends Controller
{
    /**
     * Display the yearly sales report for a specific year.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:1900|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $report = YearlySalesReport::find($request->year);

        if (!$report) {
            return response()->json(['message' => 'No yearly report found for this year.'], 404);
        }

        return response()->json($report);
    }
}
