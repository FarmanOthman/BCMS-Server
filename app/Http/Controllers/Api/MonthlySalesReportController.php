<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlySalesReport;
use Illuminate\Http\Request;

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
}
