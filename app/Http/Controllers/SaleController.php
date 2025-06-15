<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        DB::statement("select set_config('request.jwt.claims', :claims, true)", [
            'claims' => json_encode(['sub' => $user->id]),
        ]);

        // Add your sale creation logic here
        // Example:
        // $sale = Sale::create($request->all());

        return response()->json(['status' => 'ok', 'message' => 'Sale created successfully']); // Replace with your actual response
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        DB::statement("select set_config('request.jwt.claims', :claims, true)", [
            'claims' => json_encode(['sub' => $user->id]),
        ]);

        // Add your sale update logic here
        // Example:
        // $sale = Sale::findOrFail($id);
        // $sale->update($request->all());

        return response()->json(['status' => 'ok', 'message' => 'Sale updated successfully']); // Replace with your actual response
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        DB::statement("select set_config('request.jwt.claims', :claims, true)", [
            'claims' => json_encode(['sub' => $user->id]),
        ]);

        // Add your sale deletion logic here
        // Example:
        // $sale = Sale::findOrFail($id);
        // $sale->delete();

        return response()->json(['status' => 'ok', 'message' => 'Sale deleted successfully']); // Replace with your actual response
    }
}
