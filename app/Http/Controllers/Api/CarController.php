<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    }

    /**
     * Calculate total repair cost from the repair_costs JSON field.
     */
    private function calculateTotalRepairCost($repairCosts): float
    {
        $totalRepairCost = 0;
        if (!empty($repairCosts)) {
            // repair_costs is already an array due to model casting or previous json_decode
            foreach ($repairCosts as $repair) {
                if (isset($repair['cost']) && is_numeric($repair['cost'])) {
                    $totalRepairCost += (float)$repair['cost'];
                }
            }
        }
        return $totalRepairCost;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'make_id' => 'required|uuid|exists:makes,id',
            'model_id' => 'required|uuid|exists:models,id',
            'year' => 'required|integer|min:1900|max:2100',
            'base_price' => 'required|numeric|min:0',
            'public_price' => 'required|numeric|gt:0',
            'transition_cost' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['available', 'sold'])],
            'vin' => 'required|string|min:10|max:20|unique:cars,vin',
            'metadata' => 'nullable|json',
            'repair_costs' => 'nullable|json', // Input as JSON string
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $car = DB::transaction(function () use ($request, $validator) {
            Log::info('User ID before setting claims in CarController store: ' . (Auth::id() ?? 'NULL'));
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => Auth::id()]),
            ]);

            $validatedData = $validator->validated();
            unset($validatedData['sold_price']);

            $validatedData['created_by'] = Auth::id();
            $validatedData['updated_by'] = Auth::id();

            $repairCostsArray = [];
            if (isset($validatedData['repair_costs']) && is_string($validatedData['repair_costs'])) {
                $repairCostsArray = json_decode($validatedData['repair_costs'], true);
                $validatedData['repair_costs'] = $repairCostsArray;
            } elseif (isset($validatedData['repair_costs']) && is_array($validatedData['repair_costs'])) {
                $repairCostsArray = $validatedData['repair_costs'];
            }
            $validatedData['total_repair_cost'] = $this->calculateTotalRepairCost($repairCostsArray);

            if (isset($validatedData['metadata']) && is_string($validatedData['metadata'])) {
                $validatedData['metadata'] = json_decode($validatedData['metadata'], true);
            }

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
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedDataFromRequest = $request->all(); // Get all request data for validation

        $car = DB::transaction(function () use ($request, $id, $validatedDataFromRequest) {
            $currentCar = Car::findOrFail($id); // Use findOrFail to handle not found case early

            Log::info('User ID before setting claims in CarController update: ' . (Auth::id() ?? 'NULL'));
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => Auth::id()]),
            ]);

            $validator = Validator::make($validatedDataFromRequest, [
                'make_id' => 'sometimes|required|uuid|exists:makes,id',
                'model_id' => 'sometimes|required|uuid|exists:models,id',
                'year' => 'sometimes|required|integer|min:1900|max:2100',
                'base_price' => 'sometimes|required|numeric|min:0',
                'public_price' => 'sometimes|required|numeric|gt:0',
                'transition_cost' => 'nullable|numeric|min:0',
                'status' => ['sometimes', 'required', Rule::in(['available', 'sold'])],
                'vin' => 'sometimes|required|string|min:10|max:20|unique:cars,vin,' . $id,
                'metadata' => 'nullable|json',
                'repair_costs' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            $updateData = $validator->validated();
            unset($updateData['sold_price']);
            $updateData['updated_by'] = Auth::id();

            $costFieldsPotentiallyUpdated = false;
            if ($request->has('base_price') || $request->has('transition_cost') || $request->has('repair_costs')) {
                $costFieldsPotentiallyUpdated = true;
            }

            if ($request->has('repair_costs')) {
                $repairCostsArray = [];
                if (is_string($updateData['repair_costs'])) {
                    $repairCostsArray = json_decode($updateData['repair_costs'], true);
                    $updateData['repair_costs'] = $repairCostsArray;
                } elseif (is_array($updateData['repair_costs'])) {
                    $repairCostsArray = $updateData['repair_costs'];
                }
                $updateData['total_repair_cost'] = $this->calculateTotalRepairCost($repairCostsArray);
            }

            if (isset($updateData['metadata']) && is_string($updateData['metadata'])) {
                $updateData['metadata'] = json_decode($updateData['metadata'], true);
            }

            $currentCar->update($updateData);

            // After car is updated, check if cost-related fields changed and update associated sales
            if ($costFieldsPotentiallyUpdated) {
                // Refresh car model to get all attributes including those not in $updateData but potentially changed by accessors/mutators or defaults
                $currentCar->refresh(); 

                $sales = Sale::where('car_id', $currentCar->id)->get();
                foreach ($sales as $sale) {
                    $newPurchaseCost = ($currentCar->base_price ?? 0) +
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
            Cache::tags(self::CACHE_TAG_CARS_LIST)->flush();
            Log::info("Car list cache cleared (tags: [" . self::CACHE_TAG_CARS_LIST . "]) due to car deletion.");
        });

        return response()->json(['message' => 'Car deleted successfully']);
    }
}
