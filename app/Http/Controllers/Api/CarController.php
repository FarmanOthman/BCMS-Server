<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Car;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
            // total_repair_cost is not in validation as it's calculated
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validatedData = $validator->validated();
        unset($validatedData['sold_price']);

        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        // Decode repair_costs if it's a JSON string from the request
        $repairCostsArray = [];
        if (isset($validatedData['repair_costs']) && is_string($validatedData['repair_costs'])) {
            $repairCostsArray = json_decode($validatedData['repair_costs'], true);
            // Store the array format back if your model expects an array for the json column
            $validatedData['repair_costs'] = $repairCostsArray; 
        } elseif (isset($validatedData['repair_costs']) && is_array($validatedData['repair_costs'])) {
            $repairCostsArray = $validatedData['repair_costs'];
        }

        $validatedData['total_repair_cost'] = $this->calculateTotalRepairCost($repairCostsArray);

        // Convert metadata from JSON string to array if necessary
        if (isset($validatedData['metadata']) && is_string($validatedData['metadata'])) {
            $validatedData['metadata'] = json_decode($validatedData['metadata'], true);
        }

        $car = Car::create($validatedData);

        // Clear cache for the list of cars using tags
        Cache::tags(self::CACHE_TAG_CARS_LIST)->flush();
        Log::info("Car list cache cleared (tags: [" . self::CACHE_TAG_CARS_LIST . "]) due to new car creation.");

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
        $car = Car::find($id);
        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'make_id' => 'sometimes|required|uuid|exists:makes,id',
            'model_id' => 'sometimes|required|uuid|exists:models,id',
            'year' => 'sometimes|required|integer|min:1900|max:2100',
            'base_price' => 'sometimes|required|numeric|min:0',
            'public_price' => 'sometimes|required|numeric|gt:0',
            'transition_cost' => 'nullable|numeric|min:0',
            'status' => ['sometimes', 'required', Rule::in(['available', 'sold'])],
            'vin' => 'sometimes|required|string|min:10|max:20|unique:cars,vin,' . $id,
            'metadata' => 'nullable|json',
            'repair_costs' => 'nullable|json', // Input as JSON string
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validatedData = $validator->validated();
        unset($validatedData['sold_price']);
        $validatedData['updated_by'] = Auth::id();

        // Decode and calculate total_repair_cost if repair_costs is being updated
        if ($request->has('repair_costs')) {
            $repairCostsArray = [];
            if (is_string($validatedData['repair_costs'])) {
                $repairCostsArray = json_decode($validatedData['repair_costs'], true);
                $validatedData['repair_costs'] = $repairCostsArray; // Ensure it's stored as an array if model expects it
            } elseif (is_array($validatedData['repair_costs'])) {
                $repairCostsArray = $validatedData['repair_costs'];
            }
            $validatedData['total_repair_cost'] = $this->calculateTotalRepairCost($repairCostsArray);
        } else {
            // If repair_costs is not in the request, but other fields might affect it indirectly (though not in this model)
            // or if we want to ensure it's always correct based on the current DB state of repair_costs:
            // $validatedData['total_repair_cost'] = $this->calculateTotalRepairCost($car->repair_costs ?? []);
            // For now, only recalculate if repair_costs itself is provided in the update request.
        }

        // Convert metadata from JSON string to array if necessary
        if (isset($validatedData['metadata']) && is_string($validatedData['metadata'])) {
            $validatedData['metadata'] = json_decode($validatedData['metadata'], true);
        }

        $car->update($validatedData);

        // Clear cache for the specific car
        Cache::forget("car:{$id}");
        Log::info("Cache cleared for car ID: {$id} due to update.");

        // Clear cache for the list of cars using tags
        Cache::tags(self::CACHE_TAG_CARS_LIST)->flush();
        Log::info("Car list cache cleared (tags: [" . self::CACHE_TAG_CARS_LIST . "]) due to car update.");
        
        return response()->json($car->load(['make', 'model', 'createdBy', 'updatedBy']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $car = Car::find($id);
        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $car->delete();

        // Clear cache for the specific car
        Cache::forget("car:{$id}");
        Log::info("Cache cleared for car ID: {$id} due to deletion.");

        // Clear cache for the list of cars using tags
        Cache::tags(self::CACHE_TAG_CARS_LIST)->flush();
        Log::info("Car list cache cleared (tags: [" . self::CACHE_TAG_CARS_LIST . "]) due to car deletion.");

        return response()->json(['message' => 'Car deleted successfully']);
    }
}
