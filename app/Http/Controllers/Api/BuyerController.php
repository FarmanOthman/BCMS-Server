<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\StoreBuyerRequest;
use App\Http\Requests\Buyer\UpdateBuyerRequest;
use App\Models\Buyer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);
        $nameFilter = $request->query('name');
        $phoneFilter = $request->query('phone');

        // Directly query the database
        $query = Buyer::with(['createdBy', 'updatedBy']);

        if ($nameFilter) {
            $query->where('name', 'like', '%' . $nameFilter . '%');
        }

        if ($phoneFilter) {
            $query->where('phone', $phoneFilter);
        }

        $query->orderByDesc('created_at');
        
        $total = $query->count(); // Count after filtering
        $buyers = $query->skip(($page - 1) * $limit)->take($limit)->get();
        
        return response()->json([
            'data' => $buyers,
            'meta' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
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

        return response()->json($buyer->load(['createdBy', 'updatedBy']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $buyer = Buyer::with(['createdBy', 'updatedBy'])->find($id);

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

        return response()->json($buyer->load(['createdBy', 'updatedBy']));
    }

    /**
     * Remove the specified resource from storage.
     */    public function destroy(string $id): JsonResponse
    {
        $buyer = Buyer::find($id);
        if (!$buyer) {
            return response()->json(['message' => 'Buyer not found'], 404);
        }

        $buyer->delete();

        return response()->json(null, 204);
    }
}
