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
use App\Services\IntegratedSalesService; // Added import for IntegratedSalesService
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
     * Display a listing of the resource (Admin endpoint - no caching).
     */
    public function index(Request $request)
    {
        // Read pagination inputs, with defaults
        $limit = $request->query('limit', 10);
        $page  = $request->query('page', 1);
        $makeId = $request->query('make_id'); // Filter by make_id
        $modelId = $request->query('model_id'); // Filter by model_id
        $year = $request->query('year'); // Filter by year

        // Ensure limit and page are positive integers
        $limit = max(1, (int)$limit);
        $page  = max(1, (int)$page);

        Log::info("Fetching admin cars list with filters - make_id: {$makeId}, model_id: {$modelId}, year: {$year}");
        
        // Use Eloquent for querying with relationships (no caching for admin)
        $carsQuery = Car::with(['make', 'model', 'createdBy', 'updatedBy']);

        // Apply filters if provided
        if ($makeId) {
            $carsQuery->where('make_id', $makeId);
        }
        if ($modelId) {
            $carsQuery->where('model_id', $modelId);
        }
        if ($year) {
            $carsQuery->where('year', $year);
        }

        $total = $carsQuery->count();
        Log::info("Total admin records after filters, before pagination: {$total}");

        $cars = $carsQuery->orderByDesc('created_at')
                          ->skip(($page - 1) * $limit)
                          ->take($limit)
                          ->get();
        
        Log::info("Admin data fetched from database. Cars count: " . $cars->count());

        return response()->json([
            'data' => $cars,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'make_id' => $makeId,
                'model_id' => $modelId,
                'year' => $year,
                'total' => $total,
                'pages' => ceil($total / $limit)
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
    }

    /**
     * Clear public car cache for a specific car.
     */
    private function clearPublicCarCache(string $carId): void
    {
        Cache::forget("car_public:{$carId}");
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
            $this->clearPublicCarCache($newCar->id);
            Log::info("Car list cache and public cache cleared due to new car creation.");
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
     * Display public car information (filtered for public access).
     * This method returns only the information that should be visible to the public.
     */
    public function showPublic(string $id)
    {
        $cacheKey = "car_public:{$id}";
        Log::info("Attempting to retrieve public car from cache with key: {$cacheKey}");
        $cacheDuration = 60; // Cache for 1 minute
        $isCacheHit = true;

        $car = Cache::remember($cacheKey, $cacheDuration, function () use ($id, $cacheKey, &$isCacheHit) {
            Log::info("Cache miss for key: {$cacheKey}. Fetching public car from database.");
            $isCacheHit = false;
            return Car::with(['make', 'model'])->where('status', 'available')->find($id);
        });

        if ($isCacheHit) {
            Log::info("Cache hit for key: {$cacheKey}");
        }

        if (!$car) {
            return response()->json(['message' => 'Car not found or not available'], 404);
        }

        // Return only public-safe information
        $publicData = [
            'id' => $car->id,
            'year' => $car->year,
            'vin' => $car->vin,
            'public_price' => $car->public_price,
            'status' => $car->status,
            'color' => $car->color,
            'mileage' => $car->mileage,
            'description' => $car->description,
            'make' => $car->make ? [
                'id' => $car->make->id,
                'name' => $car->make->name,
            ] : null,
            'model' => $car->model ? [
                'id' => $car->model->id,
                'name' => $car->model->name,
            ] : null,
        ];

        return response()->json($publicData);
    }

    /**
     * Display a public listing of cars (filtered for public access).
     * This method returns only the information that should be visible to the public.
     */
    public function indexPublic(Request $request)
    {
        // Read pagination inputs, with defaults
        $limit = $request->query('limit', 10);
        $page  = $request->query('page', 1);
        $makeId = $request->query('make_id'); // Filter by make_id
        $modelId = $request->query('model_id'); // Filter by model_id
        $year = $request->query('year'); // Filter by year

        // Ensure limit and page are positive integers
        $limit = max(1, (int)$limit);
        $page  = max(1, (int)$page);

        // Define the cache key based on request parameters, including filters
        $cacheKey = "cars_public:page:{$page}:limit:{$limit}";
        if ($makeId) {
            $cacheKey .= ":make:{$makeId}";
        }
        if ($modelId) {
            $cacheKey .= ":model:{$modelId}";
        }
        if ($year) {
            $cacheKey .= ":year:{$year}";
        }

        Log::info("Attempting to retrieve public cars from cache with key: {$cacheKey}");

        // Cache duration in seconds (e.g., 60 seconds = 1 minute)
        $cacheDuration = 60;
        $isCacheHit = true; // Assume cache hit initially

        // Retrieve data from cache or database
        $data = Cache::remember($cacheKey, $cacheDuration, function () use ($limit, $page, $makeId, $modelId, $year, $cacheKey, &$isCacheHit) {
            Log::info("Cache miss for key: {$cacheKey}. Fetching public cars from database.");
            $isCacheHit = false; // Set to false if closure is executed
            
            // Use Eloquent for querying with relationships - only show available cars
            $carsQuery = Car::with(['make', 'model'])->where('status', 'available');

            // Apply filters if provided
            if ($makeId) {
                $carsQuery->where('make_id', $makeId);
            }
            if ($modelId) {
                $carsQuery->where('model_id', $modelId);
            }
            if ($year) {
                $carsQuery->where('year', $year);
            }

            $total = $carsQuery->count();
            Log::info("Total available public records after filters, before pagination: {$total}");

            $cars = $carsQuery->orderByDesc('created_at')
                              ->skip(($page - 1) * $limit)
                              ->take($limit)
                              ->get();
            
            Log::info("Public data fetched from database. Cars count: " . $cars->count());

            return [
                'cars' => $cars,
                'total' => $total,
            ];
        });

        if ($isCacheHit) {
            Log::info("Cache hit for key: {$cacheKey}");
        }

        // Filter the cars to only include public-safe information
        $publicCars = $data['cars']->map(function ($car) {
            return [
                'id' => $car->id,
                'year' => $car->year,
                'vin' => $car->vin,
                'public_price' => $car->public_price,
                'status' => $car->status,
                'color' => $car->color,
                'mileage' => $car->mileage,
                'description' => $car->description,
                'make' => $car->make ? [
                    'id' => $car->make->id,
                    'name' => $car->make->name,
                ] : null,
                'model' => $car->model ? [
                    'id' => $car->model->id,
                    'name' => $car->model->name,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $publicCars,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'make_id' => $makeId,
                'model_id' => $modelId,
                'year' => $year,
                'total' => $data['total'],
                'pages' => ceil($data['total'] / $limit)
            ]
        ]);
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
            $this->clearPublicCarCache($id);
            Log::info("Cache cleared for car ID: {$id} due to update.");
            $this->clearCarsListCache();
            Log::info("Car list cache and public cache cleared due to car update.");
            
            return $currentCar;
        });

        return response()->json($car->load(['make', 'model', 'createdBy', 'updatedBy']));
    }    /**
     * Sell a car - Complete sales process including buyer creation
     */
    /**
     * Sell a car with integrated buyer creation and automatic report generation.
     * 
     * This method uses the IntegratedSalesService to handle the complete sales process:
     * 1. Validates the car can be sold
     * 2. Creates a new buyer record with provided details
     * 3. Creates a sale record linking car and buyer
     * 4. Updates car status to 'sold'
     * 5. Calculates all financial metrics
     * 6. Automatically generates daily/monthly/yearly reports
     * 7. Returns comprehensive response with all details
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function sellCar(Request $request, string $id)
    {
        // Enhanced validation with better error messages
        $validated = $request->validate([
            'buyer_name' => 'required|string|max:255',
            'buyer_phone' => 'required|string|min:7|max:20',
            'buyer_address' => 'nullable|string|max:500',
            'sale_price' => 'required|numeric|min:0',
            'sale_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ], [
            'buyer_name.required' => 'Buyer name is required for the sale.',
            'buyer_phone.required' => 'Buyer phone number is required for the sale.',
            'sale_price.required' => 'Sale price is required.',
            'sale_price.min' => 'Sale price must be greater than or equal to 0.',
            'sale_date.required' => 'Sale date is required.',
            'sale_date.before_or_equal' => 'Sale date cannot be in the future.',
        ]);

        try {
            // Use the IntegratedSalesService for the complete sales process
            $integratedSalesService = new IntegratedSalesService(new ReportGenerationService());
            $result = $integratedSalesService->processSale($validated, $id);

            // Clear the cars list cache and public cache (this is specific to the controller)
            $this->clearCarsListCache();
            $this->clearPublicCarCache($id);

            return response()->json($result, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in car sale: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Validation failed for car sale',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Car not found for sale: {$id}");
            return response()->json([
                'message' => 'Car not found',
                'error' => 'The specified car does not exist in the system.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Car sale failed: ' . $e->getMessage(), [
                'car_id' => $id,
                'buyer_name' => $validated['buyer_name'] ?? 'unknown',
                'sale_price' => $validated['sale_price'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to complete car sale',
                'error' => $e->getMessage()
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
            $this->clearPublicCarCache($id);
            Log::info("Cache cleared for car ID: {$id} due to deletion.");
            $this->clearCarsListCache();
            Log::info("Car list cache and public cache cleared due to car deletion.");
        });

        return response()->json(null, 204);
    }
}
