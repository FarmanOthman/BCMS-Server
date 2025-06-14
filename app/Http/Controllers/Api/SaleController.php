<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB; // Added for database transactions

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
            'car_id' => 'required|uuid|exists:cars,id',
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
            return response()->json(['error' => 'Car is already sold.'], 409); // 409 Conflict
        }

        // Calculate total repair costs
        $totalRepairCost = 0;
        if (!empty($car->repair_costs)) {
            // Assuming repair_costs is an array of objects/arrays with a 'cost' key
            foreach ($car->repair_costs as $repair) {
                if (isset($repair['cost']) && is_numeric($repair['cost'])) {
                    $totalRepairCost += (float)$repair['cost'];
                }
            }
        }

        $purchaseCost = $car->base_price + ($car->transition_cost ?? 0) + $totalRepairCost;
        $profitLoss = $request->sale_price - $purchaseCost;

        $sale = null; // Initialize sale variable

        DB::beginTransaction();
        try {
            $sale = Sale::create([
                'car_id' => $request->car_id,
                'buyer_id' => $request->buyer_id,
                'sale_price' => $request->sale_price, // This is the actual sale price to the buyer
                'purchase_cost' => $purchaseCost, // Calculated total cost for the dealership
                'profit_loss' => $profitLoss,
                'sale_date' => $request->sale_date,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Update car status to 'sold' and set sold_price (which is the same as sale_price from the request)
            $car->status = 'sold';
            $car->sold_price = $request->sale_price; // Car's sold_price is the price it was sold to the buyer for
            $car->updated_by = Auth::id();
            $car->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the exception $e->getMessage()
            return response()->json(['error' => 'Failed to process sale. Please try again.', 'details' => $e->getMessage()], 500);
        }

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
            'buyer_id' => 'sometimes|required|uuid|exists:buyers,id',
            'sale_price' => 'sometimes|required|numeric|min:0',
            'sale_date' => 'sometimes|required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Prevent changing car_id for an existing sale
        if ($request->has('car_id') && $request->car_id !== $sale->car_id) {
            return response()->json(['error' => 'Cannot change the car associated with a sale. Please create a new sale.'], 422);
        }

        $dataToUpdate = $request->only(['buyer_id', 'sale_price', 'sale_date', 'notes']);
        $dataToUpdate['updated_by'] = Auth::id();

        DB::beginTransaction();
        try {
            $car = Car::findOrFail($sale->car_id);

            // Recalculate profit_loss if sale_price is changed
            if ($request->has('sale_price')) {
                $totalRepairCost = 0;
                if (!empty($car->repair_costs)) {
                    foreach ($car->repair_costs as $repair) {
                        if (isset($repair['cost']) && is_numeric($repair['cost'])) {
                            $totalRepairCost += (float)$repair['cost'];
                        }
                    }
                }
                $currentPurchaseCost = $car->base_price + ($car->transition_cost ?? 0) + $totalRepairCost;
                // If purchase_cost was stored accurately on sale creation, it could be used directly:
                // $currentPurchaseCost = $sale->purchase_cost;
                
                $dataToUpdate['purchase_cost'] = $currentPurchaseCost; // Ensure purchase_cost is updated if logic changes or for consistency
                $dataToUpdate['profit_loss'] = $request->sale_price - $currentPurchaseCost;
                
                // Update the car's sold_price as well
                $car->sold_price = $request->sale_price;
                $car->updated_by = Auth::id();
                // Car status should remain 'sold' unless explicitly handled otherwise
                $car->save();
            }

            $sale->update($dataToUpdate);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the exception $e->getMessage()
            return response()->json(['error' => 'Failed to update sale. Please try again.', 'details' => $e->getMessage()], 500);
        }

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
