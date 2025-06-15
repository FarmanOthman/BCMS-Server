<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
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

        // Add your car creation logic here
        // Example:
        // $car = Car::create($request->all());

        return response()->json(['status' => 'ok', 'message' => 'Car created successfully']); // Replace with your actual response
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

        // Add your car update logic here
        // Example:
        // $car = Car::findOrFail($id);
        // $car->update($request->all());

        return response()->json(['status' => 'ok', 'message' => 'Car updated successfully']); // Replace with your actual response
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

        // Add your car deletion logic here
        // Example:
        // $car = Car::findOrFail($id);
        // $car->delete();

        return response()->json(['status' => 'ok', 'message' => 'Car deleted successfully']); // Replace with your actual response
    }
}
