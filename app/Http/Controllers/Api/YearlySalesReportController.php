<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\YearlySalesReport;
use Illuminate\Http\Request;

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
        $validated = $this->validate($request, [
            'year' => 'required|integer|min:1900|max:2100',
        ]);

        $report = YearlySalesReport::find($validated['year']);

        if (!$report) {
            return response()->json(['message' => 'No yearly report found for this year.'], 404);
        }

        return response()->json($report);
    }
}
