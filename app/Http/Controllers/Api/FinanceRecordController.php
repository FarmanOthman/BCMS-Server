<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinanceRecord; // Correctly import the FinanceRecord model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log; // Ensure Log facade is imported

class FinanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Basic pagination
        $limit = $request->query('limit', 10);
        $page  = $request->query('page', 1);

        $query = FinanceRecord::query();

        // Filtering by type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filtering by category
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        
        // Filtering by date range (created_at)
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $total = $query->count();
        $financeRecords = $query->with(['createdBy', 'updatedBy'])
                                ->orderByDesc('created_at')
                                ->skip(($page - 1) * $limit)
                                ->take($limit)
                                ->get();

        return response()->json([
            'data' => $financeRecords,
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $financeRecord = DB::transaction(function () use ($request, $validator) {
                DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                    'claims' => json_encode(['sub' => Auth::id()]),
                ]);

                $validatedData = $validator->validated();
                $validatedData['created_by'] = Auth::id();
                $validatedData['updated_by'] = Auth::id();

                return FinanceRecord::create($validatedData);
            });

            return response()->json($financeRecord->load(['createdBy', 'updatedBy']), 201);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Missing or empty request.jwt.claims')) {
                 return response()->json(['error' => 'Failed to create finance record due to authentication context issue. Please try again.', 'details' => $e->getMessage()], 500);
            }
            Log::error('FinanceRecord store QueryException: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create finance record. Database error.', 'details' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('FinanceRecord store failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create finance record. Please try again.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $financeRecord = FinanceRecord::with(['createdBy', 'updatedBy'])->find($id);

        if (!$financeRecord) {
            return response()->json(['message' => 'Finance record not found'], 404);
        }
        return response()->json($financeRecord);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $financeRecord = DB::transaction(function () use ($request, $id) {
                $currentRecord = FinanceRecord::findOrFail($id);
                
                DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                    'claims' => json_encode(['sub' => Auth::id()]),
                ]);

                $validator = Validator::make($request->all(), [
                    'type' => 'sometimes|required|string|max:255',
                    'category' => 'sometimes|required|string|max:255',
                    'amount' => 'sometimes|required|numeric|min:0',
                    'description' => 'nullable|string',
                ]);
        
                if ($validator->fails()) {
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
        
                $updateData = $validator->validated();
                $updateData['updated_by'] = Auth::id();
        
                $currentRecord->update($updateData);
                return $currentRecord;
            });
            
            return response()->json($financeRecord->load(['createdBy', 'updatedBy']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Missing or empty request.jwt.claims')) {
                 return response()->json(['error' => 'Failed to update finance record due to authentication context issue. Please try again.', 'details' => $e->getMessage()], 500);
            }
            Log::error('FinanceRecord update QueryException: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update finance record. Database error.', 'details' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('FinanceRecord update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update finance record. Please try again.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $financeRecord = FinanceRecord::findOrFail($id);
                
                DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                    'claims' => json_encode(['sub' => Auth::id()]),
                ]);

                $financeRecord->delete();
            });

            return response()->json(['message' => 'Finance record deleted successfully'], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Missing or empty request.jwt.claims')) {
                 return response()->json(['error' => 'Failed to delete finance record due to authentication context issue. Please try again.', 'details' => $e->getMessage()], 500);
            }
            Log::error('FinanceRecord destroy QueryException: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete finance record. Database error.', 'details' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('FinanceRecord destroy failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete finance record. Please try again.', 'details' => $e->getMessage()], 500);
        }
    }
}
