<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $sale = DB::transaction(function () use ($request) {
            Log::info('User ID before setting claims in SaleController store: ' . (Auth::id() ?? 'NULL'));
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => Auth::id()]),
            ]);

            $car = Car::findOrFail($request->car_id);

            if ($car->status === 'sold') {
                throw new \App\Exceptions\CarAlreadySoldException('Car is already sold.');
            }

            $totalRepairCost = 0;
            if (!empty($car->repair_costs)) {
                foreach ($car->repair_costs as $repair) {
                    if (isset($repair['cost']) && is_numeric($repair['cost'])) {
                        $totalRepairCost += (float)$repair['cost'];
                    }
                }
            }

            $purchaseCost = $car->base_price + ($car->transition_cost ?? 0) + $totalRepairCost;
            $profitLoss = $request->sale_price - $purchaseCost;

            $newSale = Sale::create([
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
            
            return $newSale;
        });

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
        $validatedData = $request->all();

        $updatedSale = DB::transaction(function () use ($request, $sale, $validatedData) {
            Log::info('User ID before setting claims in SaleController update: ' . (Auth::id() ?? 'NULL'));
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => Auth::id()]),
            ]);

            $validator = Validator::make($validatedData, [
                'buyer_id' => 'sometimes|required|uuid|exists:buyers,id',
                'sale_price' => 'sometimes|required|numeric|min:0',
                'sale_date' => 'sometimes|required|date|before_or_equal:today',
                'notes' => 'nullable|string',
            ]);
    
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
    
            if ($request->has('car_id') && $request->car_id !== $sale->car_id) {
                throw new \App\Exceptions\CannotChangeCarForSaleException('Cannot change the car associated with a sale. Please create a new sale.');
            }
    
            $dataToUpdate = $validator->validated(); // Use validated data
            $dataToUpdate['updated_by'] = Auth::id();
    
            $car = Car::findOrFail($sale->car_id);
    
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
                $dataToUpdate['purchase_cost'] = $currentPurchaseCost;
                $dataToUpdate['profit_loss'] = $request->sale_price - $currentPurchaseCost;
                
                $car->sold_price = $request->sale_price;
                $car->updated_by = Auth::id();
                $car->save();
            }
    
            $sale->update($dataToUpdate);
            return $sale;
        });

        return response()->json($updatedSale->load(['car', 'buyer']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => Auth::id()]),
            ]);

            $car = Car::find($sale->car_id);

            if ($car) {
                $car->status = 'available';
                $car->sold_price = null;
                $car->updated_by = Auth::id();
                $car->save();
            }
    
            $sale->delete();
        });

        return response()->json(null, 204);
    }
}
