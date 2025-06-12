<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // Added Log facade
use Illuminate\Support\Str; // Added Str facade

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

        $offset = ($page - 1) * $limit;

        // Define the cache key based on request parameters
        $cacheKey = "cars:page:{$page}:limit:{$limit}";
        Log::info("Attempting to retrieve from cache with key: {$cacheKey}");

        // Cache duration in seconds (e.g., 60 seconds = 1 minute)
        $cacheDuration = 60;
        $isCacheHit = true; // Assume cache hit initially

        // Retrieve data from cache or database
        $data = Cache::remember($cacheKey, $cacheDuration, function () use ($offset, $limit, $cacheKey, &$isCacheHit) {
            Log::info("Cache miss for key: {$cacheKey}. Fetching from database.");
            $isCacheHit = false; // Set to false if closure is executed
            $cars = DB::table('car')
                ->select('id', 'make', 'model', 'year', 'price', 'status', 'vin', 'metadata')
                ->orderByDesc('created_at')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            $total = DB::table('car')->count();
            Log::info("Data fetched from database. Total records: {$total}");

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
