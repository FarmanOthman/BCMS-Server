<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Car; // Added Car model
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CarController extends Controller
{
    public function index(Request $request)
    {
        // Read pagination inputs, with defaults
        $limit = $request->query('limit', 10);
        $page  = $request->query('page', 1);

        // Ensure limit and page are positive integers
        $limit = max(1, (int)$limit);
        $page  = max(1, (int)$page);

        // Define the cache key based on request parameters
        $cacheKey = "cars:page:{$page}:limit:{$limit}";
        Log::info("Attempting to retrieve from cache with key: {$cacheKey}");

        // Cache duration in seconds (e.g., 60 seconds = 1 minute)
        $cacheDuration = 60;
        $isCacheHit = true; // Assume cache hit initially

        // Retrieve data from cache or database
        $data = Cache::remember($cacheKey, $cacheDuration, function () use ($limit, $page, $cacheKey, &$isCacheHit) {
            Log::info("Cache miss for key: {$cacheKey}. Fetching from database.");
            $isCacheHit = false; // Set to false if closure is executed
            
            // Use Eloquent for querying with relationships
            $carsQuery = Car::with(['make', 'model']);

            $total = $carsQuery->count();
            Log::info("Total records before pagination: {$total}");

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
                'total' => $data['total'],
                'pages' => ceil($data['total'] / $limit)
            ]
        ]);
    }
}
