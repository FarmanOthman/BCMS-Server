<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\StoreCarRequest;
use App\Http\Requests\Car\UpdateCarRequest;
use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\Sale; // Added import for Sale model
use App\Models\Buyer; // Added import for Buyer model
use App\Services\ReportGenerationService; // Added import for ReportGenerationService
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    const CACHE_PREFIX_CARS_LIST = 'cars_list';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Read pagination inputs, with defaults
        $limit = $request->query('limit', 10);
        $page  = $request->query('page', 1);
        $makeId = $request->query('make_id'); // Filter by make_id
        $modelId = $request->query('model_id'); // Filter by model_id

        // Ensure limit and page are positive integers
        $limit = max(1, (int)$limit);
        $page  = max(1, (int)$page);

        // Define the cache key based on request parameters, including filters
        $cacheKey = "cars:page:{$page}:limit:{$limit}";
        if ($makeId) {
            $cacheKey .= ":make:{$makeId}";
        }
        if ($modelId) {
            $cacheKey .= ":model:{$modelId}";
        }

        Log::info("Attempting to retrieve from cache with key: {$cacheKey}");

        // Cache duration in seconds (e.g., 60 seconds = 1 minute)
        $cacheDuration = 60;
        $isCacheHit = true; // Assume cache hit initially

        // Retrieve data from cache or database
        $data = Cache::remember($cacheKey, $cacheDuration, function () use ($limit, $page, $makeId, $modelId, $cacheKey, &$isCacheHit) {
            Log::info("Cache miss for key: {$cacheKey}. Fetching from database.");
            $isCacheHit = false; // Set to false if closure is executed
            
            // Use Eloquent for querying with relationships
            $carsQuery = Car::with(['make', 'model', 'createdBy', 'updatedBy']);

            // Apply filters if provided
            if ($makeId) {
                $carsQuery->where('make_id', $makeId);
            }
            if ($modelId) {
                $carsQuery->where('model_id', $modelId);
            }

            $total = $carsQuery->count();
            Log::info("Total records after filters, before pagination: {$total}");

            $cars = $carsQuery->orderByDesc('created_at')
                              ->skip(($page - 1) * $limit)
                              ->take($limit)
                              ->get();
            
            Log::info("Data fetched from database. Cars count: " . $cars->count());

            return [
                'cars' => $cars,
                'total' => $total,
            ];
        });

        if ($isCacheHit) {
            Log::info("Cache hit for key: {$cacheKey}");
        }

        return response()->json([
            'data' => $data['cars'],
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'make_id' => $makeId, // Include filter params in meta
                'model_id' => $modelId,
                'total' => $data['total'],
                'pages' => ceil($data['total'] / $limit)
            ]
        ]);
    }    /**
     * Calculate total repair cost from the repair_items JSON field.
     */
    private function calculateTotalRepairCost($repairItems): float
    {
        $totalRepairCost = 0;
        if (!empty($repairItems)) {
            // Handle both string and array inputs
            if (is_string($repairItems)) {
                $repairItems = json_decode($repairItems, true);
            }
            
            if (is_array($repairItems)) {
                foreach ($repairItems as $repair) {
                    if (isset($repair['cost']) && is_numeric($repair['cost'])) {
                        $totalRepairCost += (float)$repair['cost'];
                    }
                }
            }
        }
        return $totalRepairCost;
    }

    /**
     * Clear all cars list cache entries by clearing cache keys that start with the cars list prefix.
     */
    private function clearCarsListCache(): void
    {
        // For file/database cache drivers, we can't easily clear by pattern
        // So we'll clear the entire cache when cars are modified
        // This is a simple approach that works with all cache drivers
        Cache::flush();
    }/**
     * Store a newly created resource in storage.
     */
    public function store(StoreCarRequest $request)
    {
        $validatedData = $request->validated();

        $car = DB::transaction(function () use ($request, $validatedData) {
            Log::info('User ID before setting claims in CarController store: ' . (Auth::id() ?? 'NULL'));
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => Auth::id()]),
            ]);

            unset($validatedData['sold_price']);

            $validatedData['created_by'] = Auth::id();
            $validatedData['updated_by'] = Auth::id();            $repairItemsArray = [];
            if (isset($validatedData['repair_items']) && is_string($validatedData['repair_items'])) {
                $repairItemsArray = json_decode($validatedData['repair_items'], true);
                $validatedData['repair_items'] = $repairItemsArray;
            } elseif (isset($validatedData['repair_items']) && is_array($validatedData['repair_items'])) {
                $repairItemsArray = $validatedData['repair_items'];
            }
            $validatedData['total_repair_cost'] = $this->calculateTotalRepairCost($repairItemsArray);

            $newCar = Car::create($validatedData);

            $this->clearCarsListCache();
            Log::info("Car list cache cleared due to new car creation.");
            return $newCar;
        });

        return response()->json($car->load(['make', 'model', 'createdBy', 'updatedBy']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cacheKey = "car:{$id}";
        Log::info("Attempting to retrieve car from cache with key: {$cacheKey}");
        $cacheDuration = 60; // Cache for 1 minute
        $isCacheHit = true;

        $car = Cache::remember($cacheKey, $cacheDuration, function () use ($id, $cacheKey, &$isCacheHit) {
            Log::info("Cache miss for key: {$cacheKey}. Fetching car from database.");
            $isCacheHit = false;
            return Car::with(['make', 'model', 'createdBy', 'updatedBy'])->find($id);
        });

        if ($isCacheHit) {
            Log::info("Cache hit for key: {$cacheKey}");
        }

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }
        return response()->json($car);
    }    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCarRequest $request, string $id)
    {
        $validatedData = $request->validated();

        $car = DB::transaction(function () use ($request, $id, $validatedData) {
            $currentCar = Car::findOrFail($id); // Use findOrFail to handle not found case early

            Log::info('User ID before setting claims in CarController update: ' . (Auth::id() ?? 'NULL'));
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => Auth::id()]),
            ]);

            unset($validatedData['sold_price']);
            $validatedData['updated_by'] = Auth::id();            $costFieldsPotentiallyUpdated = false;
            if ($request->has('cost_price') || $request->has('transition_cost') || $request->has('repair_items')) {
                $costFieldsPotentiallyUpdated = true;
            }            if ($request->has('repair_items')) {
                $repairItemsArray = [];
                if (is_string($validatedData['repair_items'])) {
                    $repairItemsArray = json_decode($validatedData['repair_items'], true);
                    $validatedData['repair_items'] = $repairItemsArray;
                } elseif (is_array($validatedData['repair_items'])) {
                    $repairItemsArray = $validatedData['repair_items'];
                }
                $validatedData['total_repair_cost'] = $this->calculateTotalRepairCost($repairItemsArray);
            }

            $currentCar->update($validatedData);

            // After car is updated, check if cost-related fields changed and update associated sales
            if ($costFieldsPotentiallyUpdated) {
                // Refresh car model to get all attributes including those not in $updateData but potentially changed by accessors/mutators or defaults
                $currentCar->refresh(); 

                $sales = Sale::where('car_id', $currentCar->id)->get();
                foreach ($sales as $sale) {
                    $newPurchaseCost = ($currentCar->cost_price ?? 0) +
                                     ($currentCar->transition_cost ?? 0) +
                                     ($currentCar->total_repair_cost ?? 0);

                    $newProfitLoss = $sale->sale_price - $newPurchaseCost;

                    if ($sale->purchase_cost != $newPurchaseCost || $sale->profit_loss != $newProfitLoss) {
                        $sale->update([
                            'purchase_cost' => $newPurchaseCost,
                            'profit_loss' => $newProfitLoss,
                            'updated_by' => Auth::id(),
                        ]);
                        Log::info("Updated Sale ID {$sale->id} due to Car ID {$currentCar->id} cost change. New Purchase Cost: {$newPurchaseCost}, New Profit/Loss: {$newProfitLoss}");
                    }
                }
            }

            Cache::forget("car:{$id}");
            Log::info("Cache cleared for car ID: {$id} due to update.");
            $this->clearCarsListCache();
            Log::info("Car list cache cleared due to car update.");
            
            return $currentCar;
        });

        return response()->json($car->load(['make', 'model', 'createdBy', 'updatedBy']));
    }    /**
     * Sell a car - Complete sales process including buyer creation
     */
    public function sellCar(Request $request, string $id)
    {
        $validated = $request->validate([
            'buyer_name' => 'required|string|max:255',
            'buyer_phone' => 'required|string|min:7|max:20|unique:buyer,phone',
            'buyer_address' => 'nullable|string',
            'sale_price' => 'required|numeric|min:0',
            'sale_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = DB::transaction(function () use ($validated, $id) {
                // Set JWT claims for RLS
                $userId = Auth::user() ? Auth::user()->id : null;
                DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                    'claims' => json_encode(['sub' => $userId]),
                ]);

                $car = Car::findOrFail($id);

                if ($car->status === 'sold') {
                    throw new \Exception('Car is already sold.');
                }

                // Create buyer
                $buyer = Buyer::create([
                    'name' => $validated['buyer_name'],
                    'phone' => $validated['buyer_phone'],
                    'address' => $validated['buyer_address'] ?? null,
                    'car_ids' => [$car->id],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                // Calculate total repair costs using the existing method
                $totalRepairCost = $this->calculateTotalRepairCost($car->repair_items);

                // Calculate purchase cost exactly like in comprehensive sales test
                $purchaseCost = (float)$car->cost_price + (float)($car->transition_cost ?? 0) + (float)$totalRepairCost;
                $profitLoss = (float)$validated['sale_price'] - (float)$purchaseCost;

                // Create sale with comprehensive data
                $sale = Sale::create([
                    'car_id' => $car->id,
                    'buyer_id' => $buyer->id,
                    'sale_price' => (float)$validated['sale_price'],
                    'purchase_cost' => (float)$purchaseCost,
                    'profit_loss' => (float)$profitLoss,
                    'sale_date' => $validated['sale_date'],
                    'notes' => $validated['notes'] ?? "Sale of {$car->year} {$car->make->name} {$car->model->name}",
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                // Update car status to 'sold' and set selling_price
                $car->status = 'sold';
                $car->selling_price = (float)$validated['sale_price'];
                $car->updated_by = $userId;
                $car->save();

                // Clear cache
                Cache::forget("car:{$id}");
                $this->clearCarsListCache();

                // Generate reports automatically for the sale date
                try {
                    $reportService = new ReportGenerationService();
                    $reportService->generateReportsForSale($validated['sale_date']);
                    Log::info("Automatically generated reports for sale date: {$validated['sale_date']}");
                } catch (\Exception $e) {
                    Log::error("Failed to generate reports for sale date {$validated['sale_date']}: " . $e->getMessage());
                    // Don't fail the sale if report generation fails
                }

                // Return comprehensive response with detailed financial breakdown
                return [
                    'sale' => $sale->load(['car', 'car.make', 'car.model', 'buyer']),
                    'buyer' => $buyer,
                    'car' => $car->load(['make', 'model']),
                    'financial_summary' => [
                        'sale_price' => (float)$validated['sale_price'],
                        'purchase_cost' => (float)$purchaseCost,
                        'profit_loss' => (float)$profitLoss,
                        'profit_margin' => $purchaseCost > 0 ? round(($profitLoss / $purchaseCost) * 100, 2) : 0,
                        'cost_breakdown' => [
                            'base_cost' => (float)$car->cost_price,
                            'transition_cost' => (float)($car->transition_cost ?? 0),
                            'repair_cost' => (float)$totalRepairCost,
                            'total_purchase_cost' => (float)$purchaseCost,
                        ],
                        'repair_items' => is_array($car->repair_items) ? $car->repair_items : json_decode($car->repair_items, true) ?? [],
                    ]
                ];
            });

            return response()->json($result, 201);

        } catch (\Exception $e) {
            Log::error('Car sale failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to process car sale. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $car = Car::findOrFail($id);

            Log::info('User ID before setting claims in CarController destroy: ' . (Auth::id() ?? 'NULL'));
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => Auth::id()]),
            ]);

            $car->delete();

            Cache::forget("car:{$id}");
            Log::info("Cache cleared for car ID: {$id} due to deletion.");
            $this->clearCarsListCache();
            Log::info("Car list cache cleared due to car deletion.");
        });

        return response()->json(null, 204);
    }
}
