<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\StoreCarRequest;
use App\Http\Requests\Car\UpdateCarRequest;
use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\Sale; // Added import for Sale model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    const CACHE_TAG_CARS_LIST = 'cars_list';

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

        Log::info("Attempting to retrieve from cache with key: {$cacheKey} using tags: [" . self::CACHE_TAG_CARS_LIST . "]");

        // Cache duration in seconds (e.g., 60 seconds = 1 minute)
        $cacheDuration = 60;
        $isCacheHit = true; // Assume cache hit initially

        // Retrieve data from cache or database using tags
        $data = Cache::tags(self::CACHE_TAG_CARS_LIST)->remember($cacheKey, $cacheDuration, function () use ($limit, $page, $makeId, $modelId, $cacheKey, &$isCacheHit) {
            Log::info("Cache miss for key: {$cacheKey} (tags: [" . self::CACHE_TAG_CARS_LIST . "]). Fetching from database.");
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
            Log::info("Cache hit for key: {$cacheKey} (tags: [" . self::CACHE_TAG_CARS_LIST . "])");
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
            // repair_items is already an array due to model casting or previous json_decode
            foreach ($repairItems as $repair) {
                if (isset($repair['cost']) && is_numeric($repair['cost'])) {
                    $totalRepairCost += (float)$repair['cost'];
                }
            }
        }
        return $totalRepairCost;
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

            Cache::tags(self::CACHE_TAG_CARS_LIST)->flush();
            Log::info("Car list cache cleared (tags: [" . self::CACHE_TAG_CARS_LIST . "]) due to new car creation.");
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
            Cache::tags(self::CACHE_TAG_CARS_LIST)->flush();
            Log::info("Car list cache cleared (tags: [" . self::CACHE_TAG_CARS_LIST . "]) due to car update.");
            
            return $currentCar;
        });

        return response()->json($car->load(['make', 'model', 'createdBy', 'updatedBy']));
    }    /**
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
            Cache::tags(self::CACHE_TAG_CARS_LIST)->flush();
            Log::info("Car list cache cleared (tags: [" . self::CACHE_TAG_CARS_LIST . "]) due to car deletion.");
        });

        return response()->json(null, 204);
    }
}
