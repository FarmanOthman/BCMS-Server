<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Car; // Added for car status update
use Illuminate\Http\Request; // Ensure Request is imported
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; // Added for Rule import

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Sale::query()->with(['car', 'buyer']);

        // Filter by car_id
        if ($request->has('car_id')) {
            $query->where('car_id', $request->input('car_id'));
        }

        // Filter by buyer_id
        if ($request->has('buyer_id')) {
            $query->where('buyer_id', $request->input('buyer_id'));
        }

        // Filter by sale_date (exact match, range, etc.)
        if ($request->has('sale_date')) { // Exact date match
            $query->whereDate('sale_date', $request->input('sale_date'));
        }
        if ($request->has('sale_date_from')) {
            $query->whereDate('sale_date', '>=', $request->input('sale_date_from'));
        }
        if ($request->has('sale_date_to')) {
            $query->whereDate('sale_date', '<=', $request->input('sale_date_to'));
        }

        // Filter by sale_price (exact match, range, etc.)
        if ($request->has('sale_price')) { // Exact price match
            $query->where('sale_price', $request->input('sale_price'));
        }
        if ($request->has('min_sale_price')) {
            $query->where('sale_price', '>=', $request->input('min_sale_price'));
        }
        if ($request->has('max_sale_price')) {
            $query->where('sale_price', '<=', $request->input('max_sale_price'));
        }

        return $query->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_id' => [
                'required',
                'uuid',
                'exists:cars,id',
                Rule::unique('sales')->where(function ($query) use ($request) {
                    // Allow multiple sales for different cars, but a car can only be sold once actively.
                    // This check is more robustly handled by checking car status before sale.
                    return $query->where('car_id', $request->car_id);
                })->ignore(null, 'id'), // Ensure this car_id is not already in sales table
            ],
            'buyer_id' => 'required|uuid|exists:buyers,id',
            'sale_price' => 'required|numeric|min:0',
            'sale_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $car = Car::findOrFail($request->car_id);

        if ($car->status === 'sold') {
            return response()->json(['error' => 'Car is already sold.'], 409); 
        }

        $purchaseCost = $car->base_price + $car->transition_cost;
        $profitLoss = $request->sale_price - $purchaseCost;

        $sale = Sale::create([
            'car_id' => $request->car_id,
            'buyer_id' => $request->buyer_id,
            'sale_price' => $request->sale_price,
            'purchase_cost' => $purchaseCost,
            'profit_loss' => $profitLoss,
            'sale_date' => $request->sale_date,
            'notes' => $request->notes,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $car->status = 'sold';
        $car->sold_price = $request->sale_price;
        $car->updated_by = Auth::id();
        $car->save();

        return response()->json($sale->load(['car', 'buyer']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        return $sale->load(['car', 'buyer']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        $validator = Validator::make($request->all(), [
            // car_id should generally not be updatable for a sale, 
            // as it would imply changing the fundamental item that was sold.
            // If a mistake was made, the sale should be deleted and recreated.
            // 'car_id' => 'sometimes|required|uuid|exists:cars,id',
            'buyer_id' => 'sometimes|required|uuid|exists:buyers,id',
            'sale_price' => 'sometimes|required|numeric|min:0',
            'sale_date' => 'sometimes|required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $dataToUpdate = $request->only(['buyer_id', 'sale_price', 'sale_date', 'notes']);
        $dataToUpdate['updated_by'] = Auth::id();

        // Recalculate profit_loss if sale_price is changed
        if ($request->has('sale_price')) {
            // Fetch the original car associated with this sale to get its costs
            $car = Car::findOrFail($sale->car_id);
            $purchaseCost = $car->base_price + $car->transition_cost; // Or use $sale->purchase_cost if it's guaranteed to be accurate
            $dataToUpdate['profit_loss'] = $request->sale_price - $purchaseCost;
            
            // Update the car's sold_price as well
            if ($car->status === 'sold' && $car->id === $sale->car_id) { // Ensure we are updating the correct car
                $car->sold_price = $request->sale_price;
                $car->updated_by = Auth::id();
                $car->save();
            }
        }

        $sale->update($dataToUpdate);

        return response()->json($sale->load(['car', 'buyer']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        $car = Car::find($sale->car_id);

        // Revert car status to 'available' and clear sold_price if the car is found
        if ($car) {
            $car->status = 'available'; // Or a more sophisticated status management if needed
            $car->sold_price = null;
            $car->updated_by = Auth::id();
            $car->save();
        }

        $sale->delete();

        return response()->json(null, 204);
    }
}
