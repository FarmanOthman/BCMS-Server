<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Model as CarModel; // Alias to avoid class name conflict
use App\Http\Requests\StoreModelRequest;
use App\Http\Requests\UpdateModelRequest;

class ModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Eager load the 'make' relationship and order by make name, then model name
        return CarModel::with('make')->orderBy(function ($query) {
            $query->select('name')
                ->from('makes')
                ->whereColumn('makes.id', 'models.make_id');
        })->orderBy('name')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreModelRequest $request)
    {
        $model = CarModel::create($request->validated());
        $model->load('make'); // Load the make relationship for the response
        return response()->json($model, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CarModel $model)
    {
        return $model->load('make'); // Eager load make relationship
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateModelRequest $request, CarModel $model)
    {
        $model->update($request->validated());
        $model->load('make'); // Load the make relationship for the response
        return response()->json($model);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CarModel $model)
    {
        // Consider what to do with associated cars (cascade delete, set null, prevent deletion if cars exist)
        // This is handled by database constraints or model events if configured.
        $model->delete();
        return response()->json(null, 204);
    }
}
