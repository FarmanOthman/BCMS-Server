<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Make;
use App\Http\Requests\StoreMakeRequest;
use App\Http\Requests\UpdateMakeRequest;

class MakeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Make::orderBy('name')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMakeRequest $request)
    {
        $make = Make::create($request->validated());
        return response()->json($make, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Make $make)
    {
        return $make;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMakeRequest $request, Make $make)
    {
        $make->update($request->validated());
        return response()->json($make);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Make $make)
    {
        $make->delete();
        return response()->json(null, 204);
    }
}
