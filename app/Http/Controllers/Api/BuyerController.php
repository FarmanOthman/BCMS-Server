<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\StoreBuyerRequest;
use App\Http\Requests\Buyer\UpdateBuyerRequest;
use App\Models\Buyer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BuyerController extends Controller
{
    const CACHE_TAG_BUYERS_LIST = 'buyers_list';
    const CACHE_KEY_BUYER_ITEM_PREFIX = 'buyer_item_';
    const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $cacheKey = "buyers:page:{$page}:limit:{$limit}";

        Log::info("Attempting to retrieve buyers from cache with key: {$cacheKey} using tags: [" . self::CACHE_TAG_BUYERS_LIST . "]");

        $data = Cache::tags(self::CACHE_TAG_BUYERS_LIST)->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($limit, $page, $cacheKey) {
            Log::info("Cache miss for buyers list key: {$cacheKey}. Fetching from database.");
            $query = Buyer::with(['createdBy', 'updatedBy'])->orderByDesc('created_at');
            $total = $query->count();
            $buyers = $query->skip(($page - 1) * $limit)->take($limit)->get();
            return ['buyers' => $buyers, 'total' => $total];
        });

        return response()->json([
            'data' => $data['buyers'],
            'meta' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => $data['total'],
                'pages' => ceil($data['total'] / $limit)
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBuyerRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        $buyer = Buyer::create($validatedData);

        Cache::tags(self::CACHE_TAG_BUYERS_LIST)->flush();
        Log::info("Buyers list cache flushed due to new buyer creation.");

        return response()->json($buyer->load(['createdBy', 'updatedBy']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $cacheKey = self::CACHE_KEY_BUYER_ITEM_PREFIX . $id;
        Log::info("Attempting to retrieve buyer from cache with key: {$cacheKey}");

        $buyer = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($id, $cacheKey) {
            Log::info("Cache miss for buyer item key: {$cacheKey}. Fetching from database.");
            return Buyer::with(['createdBy', 'updatedBy'])->find($id);
        });

        if (!$buyer) {
            return response()->json(['message' => 'Buyer not found'], 404);
        }
        return response()->json($buyer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBuyerRequest $request, string $id): JsonResponse
    {
        $buyer = Buyer::find($id);
        if (!$buyer) {
            return response()->json(['message' => 'Buyer not found'], 404);
        }

        $validatedData = $request->validated();
        $validatedData['updated_by'] = Auth::id();

        $buyer->update($validatedData);

        Cache::forget(self::CACHE_KEY_BUYER_ITEM_PREFIX . $id);
        Cache::tags(self::CACHE_TAG_BUYERS_LIST)->flush();
        Log::info("Cache flushed for buyer item {$id} and buyers list due to update.");

        return response()->json($buyer->load(['createdBy', 'updatedBy']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $buyer = Buyer::find($id);
        if (!$buyer) {
            return response()->json(['message' => 'Buyer not found'], 404);
        }

        $buyer->delete();

        Cache::forget(self::CACHE_KEY_BUYER_ITEM_PREFIX . $id);
        Cache::tags(self::CACHE_TAG_BUYERS_LIST)->flush();
        Log::info("Cache flushed for buyer item {$id} and buyers list due to deletion.");

        return response()->json(['message' => 'Buyer deleted successfully']);
    }
}
