<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CarAlreadySoldException;
use Exception;


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
        $validated = $this->validate($request, [
            'car_id' => 'required|uuid|exists:cars,id',
            'buyer_id' => 'required|uuid|exists:buyer,id', // Corrected table name to buyer
            'sale_price' => 'required|numeric|min:0',
            'sale_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            $sale = DB::transaction(function () use ($validated) {
                // Set JWT claims for RLS. This must be within the transaction.
                $userId = Auth::user() ? Auth::user()->id : null;
                DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                    'claims' => json_encode(['sub' => $userId]),
                ]);

                $car = Car::findOrFail($validated['car_id']);

                if ($car->status === 'sold') {
                    throw new CarAlreadySoldException('Car is already sold.');
                }

                // Calculate total repair costs
                $totalRepairCost = 0;
                if (!empty($car->repair_items)) {
                    foreach ($car->repair_items as $repair) {
                        if (isset($repair['cost']) && is_numeric($repair['cost'])) {
                            $totalRepairCost += (float)$repair['cost'];
                        }
                    }
                }

                $purchaseCost = $car->cost_price + ($car->transition_cost ?? 0) + $totalRepairCost;
                $profitLoss = $validated['sale_price'] - $purchaseCost;

                $newSale = Sale::create([
                    'car_id' => $validated['car_id'],
                    'buyer_id' => $validated['buyer_id'],
                    'sale_price' => $validated['sale_price'],
                    'purchase_cost' => $purchaseCost,
                    'profit_loss' => $profitLoss,
                    'sale_date' => $validated['sale_date'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                // Update car status to 'sold' and set selling_price
                $car->status = 'sold';
                $car->selling_price = $validated['sale_price'];
                $car->updated_by = $userId;
                $car->save();
                
                return $newSale;
            });

            return response()->json($sale->load(['car', 'buyer']), 201);

        } catch (CarAlreadySoldException $e) {
            return response()->json(['error' => $e->getMessage()], 409); // 409 Conflict
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Missing or empty request.jwt.claims')) {
                 return response()->json(['error' => 'Failed to process sale due to authentication context issue. Please try again.', 'details' => $e->getMessage()], 500);
            }
            Log::error('Sale creation QueryException: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process sale. Database error.', 'details' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        } catch (Exception $e) {
            Log::error('Sale creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process sale. Please try again.', 'details' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
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
        $validated = $this->validate($request, [
            'buyer_id' => 'sometimes|required|uuid|exists:buyer,id', // Corrected table name to buyer
            'sale_price' => 'sometimes|required|numeric|min:0',
            'sale_date' => 'sometimes|required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        if ($request->has('car_id') && $request->car_id !== $sale->car_id) {
            return response()->json(['error' => 'Cannot change the car associated with a sale. Please create a new sale.'], 422);
        }

        $dataToUpdate = array_intersect_key($validated, array_flip(['buyer_id', 'sale_price', 'sale_date', 'notes']));

        try {
            $updatedSale = DB::transaction(function () use ($sale, $dataToUpdate, $validated) {
                $userId = Auth::user() ? Auth::user()->id : null;
                DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                    'claims' => json_encode(['sub' => $userId]),
                ]);

                $dataToUpdate['updated_by'] = $userId; // Set updated_by after Auth::id() is confirmed available

                $car = Car::findOrFail($sale->car_id);

                if (isset($validated['sale_price'])) {
                    // We want to keep the original purchase_cost from the sale
                    // not recalculate it, which causes the test to fail
                    $dataToUpdate['profit_loss'] = $validated['sale_price'] - $sale->purchase_cost;
                    
                    $car->selling_price = $validated['sale_price'];
                    $car->updated_by = $userId;
                    $car->save();
                }

                $sale->update($dataToUpdate);
                return $sale;
            });

            return response()->json($updatedSale->load(['car', 'buyer']));

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Missing or empty request.jwt.claims')) {
                 return response()->json(['error' => 'Failed to update sale due to authentication context issue. Please try again.', 'details' => $e->getMessage()], 500);
            }
            Log::error('Sale update QueryException: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update sale. Database error.', 'details' => $e->getMessage()], 500);
        } catch (Exception $e) {
            Log::error('Sale update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update sale. Please try again.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        try {
            DB::transaction(function () use ($sale) {
                $userId = Auth::user() ? Auth::user()->id : null;
                DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                    'claims' => json_encode(['sub' => $userId]),
                ]);

                $car = Car::find($sale->car_id);

                if ($car) {
                    $car->status = 'available';
                    $car->selling_price = null;
                    $car->updated_by = $userId; // Use $userId instead of Auth::id()
                    $car->save();
                }

                $sale->delete();
            });

            return response()->json(null, 204);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Missing or empty request.jwt.claims')) {
                 return response()->json(['error' => 'Failed to delete sale due to authentication context issue. Please try again.', 'details' => $e->getMessage()], 500);
            }
            Log::error('Sale deletion QueryException: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete sale. Database error.', 'details' => $e->getMessage()], 500);
        } catch (Exception $e) {
            Log::error('Sale deletion failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete sale. Please try again.', 'details' => $e->getMessage()], 500);
        }
    }
}
