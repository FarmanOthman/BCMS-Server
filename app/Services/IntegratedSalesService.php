<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Buyer;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class IntegratedSalesService
{
    protected $reportGenerationService;

    public function __construct(ReportGenerationService $reportGenerationService)
    {
        $this->reportGenerationService = $reportGenerationService;
    }

    /**
     * Complete sales process with integrated buyer creation.
     * 
     * This method handles the entire sales workflow:
     * 1. Validates car availability
     * 2. Creates buyer record
     * 3. Creates sale record
     * 4. Updates car status
     * 5. Generates reports
     * 6. Returns comprehensive response
     *
     * @param array $saleData
     * @param string $carId
     * @return array
     * @throws \Exception
     */
    public function processSale(array $saleData, string $carId): array
    {
        return DB::transaction(function () use ($saleData, $carId) {
            // Set JWT claims for RLS
            $userId = Auth::user() ? Auth::user()->id : null;
            DB::statement("select set_config('request.jwt.claims', :claims, true)", [
                'claims' => json_encode(['sub' => $userId]),
            ]);

            // Find and validate car
            $car = $this->validateAndGetCar($carId);

            // Create buyer
            $buyer = $this->createBuyer($saleData, $car->id, $userId);

            // Calculate financial metrics
            $financialMetrics = $this->calculateFinancialMetrics($car, $saleData['sale_price']);

            // Create sale record
            $sale = $this->createSaleRecord($saleData, $car->id, $buyer->id, $financialMetrics, $userId);

            // Update car status
            $this->updateCarStatus($car, $saleData['sale_price'], $userId);

            // Clear caches
            $this->clearCaches($carId);

            // Generate reports
            $this->generateReports($saleData['sale_date']);

            // Return comprehensive response
            return $this->prepareResponse($sale, $buyer, $car, $financialMetrics);
        });
    }

    /**
     * Validate car availability and return car with relationships.
     *
     * @param string $carId
     * @return Car
     * @throws \Exception
     */
    protected function validateAndGetCar(string $carId): Car
    {
        $car = Car::with(['make', 'model'])->findOrFail($carId);

        if ($car->status === 'sold') {
            throw new \Exception("Car with VIN {$car->vin} is already sold and cannot be sold again.");
        }

        if ($car->status !== 'available') {
            throw new \Exception("Car with VIN {$car->vin} is not available for sale (current status: {$car->status}).");
        }

        return $car;
    }

    /**
     * Create buyer record with provided details.
     *
     * @param array $saleData
     * @param string $carId
     * @param string|null $userId
     * @return Buyer
     */
    protected function createBuyer(array $saleData, string $carId, ?string $userId): Buyer
    {
        $buyer = Buyer::create([
            'name' => trim($saleData['buyer_name']),
            'phone' => trim($saleData['buyer_phone']),
            'address' => $saleData['buyer_address'] ? trim($saleData['buyer_address']) : null,
            'car_ids' => [$carId], // Link buyer to this specific car
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Log::info("Created buyer: {$buyer->name} (ID: {$buyer->id}) for car sale");

        return $buyer;
    }

    /**
     * Calculate all financial metrics for the sale.
     *
     * @param Car $car
     * @param float $salePrice
     * @return array
     */
    protected function calculateFinancialMetrics(Car $car, float $salePrice): array
    {
        $totalRepairCost = $this->calculateTotalRepairCost($car->repair_items);
        $purchaseCost = (float)$car->cost_price + (float)($car->transition_cost ?? 0) + (float)$totalRepairCost;
        $profitLoss = (float)$salePrice - (float)$purchaseCost;
        $profitMargin = $purchaseCost > 0 ? round(($profitLoss / $purchaseCost) * 100, 2) : 0;

        return [
            'total_repair_cost' => $totalRepairCost,
            'purchase_cost' => $purchaseCost,
            'profit_loss' => $profitLoss,
            'profit_margin' => $profitMargin,
        ];
    }

    /**
     * Calculate total repair cost from repair items.
     *
     * @param mixed $repairItems
     * @return float
     */
    protected function calculateTotalRepairCost($repairItems): float
    {
        if (empty($repairItems)) {
            return 0.0;
        }

        $items = is_array($repairItems) ? $repairItems : json_decode($repairItems, true);
        
        if (!is_array($items)) {
            return 0.0;
        }

        return array_sum(array_column($items, 'cost'));
    }

    /**
     * Create sale record with all necessary data.
     *
     * @param array $saleData
     * @param string $carId
     * @param string $buyerId
     * @param array $financialMetrics
     * @param string|null $userId
     * @return Sale
     */
    protected function createSaleRecord(array $saleData, string $carId, string $buyerId, array $financialMetrics, ?string $userId): Sale
    {
        $car = Car::with(['make', 'model'])->find($carId);
        
        $sale = Sale::create([
            'car_id' => $carId,
            'buyer_id' => $buyerId,
            'sale_price' => (float)$saleData['sale_price'],
            'purchase_cost' => (float)$financialMetrics['purchase_cost'],
            'profit_loss' => (float)$financialMetrics['profit_loss'],
            'sale_date' => $saleData['sale_date'],
            'notes' => $saleData['notes'] ?? "Sale of {$car->year} {$car->make->name} {$car->model->name}",
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Log::info("Created sale record: Sale ID {$sale->id} for car {$car->vin}");

        return $sale;
    }

    /**
     * Update car status to sold and set selling price.
     *
     * @param Car $car
     * @param float $salePrice
     * @param string|null $userId
     * @return void
     */
    protected function updateCarStatus(Car $car, float $salePrice, ?string $userId): void
    {
        $car->status = 'sold';
        $car->selling_price = (float)$salePrice;
        $car->updated_by = $userId;
        $car->save();

        Log::info("Updated car status to 'sold': {$car->vin}");
    }

    /**
     * Clear relevant caches.
     *
     * @param string $carId
     * @return void
     */
    protected function clearCaches(string $carId): void
    {
        Cache::forget("car:{$carId}");
        // Note: clearCarsListCache() would need to be called from the controller
        // as it's a private method in CarController
    }

    /**
     * Generate reports for the sale date.
     *
     * @param string $saleDate
     * @return void
     */
    protected function generateReports(string $saleDate): void
    {
        try {
            $this->reportGenerationService->generateReportsForSale($saleDate);
            Log::info("Successfully generated reports for sale date: {$saleDate}");
        } catch (\Exception $e) {
            Log::error("Failed to generate reports for sale date {$saleDate}: " . $e->getMessage());
            // Don't fail the sale if report generation fails - this is non-critical
        }
    }

    /**
     * Prepare comprehensive response with all sale details.
     *
     * @param Sale $sale
     * @param Buyer $buyer
     * @param Car $car
     * @param array $financialMetrics
     * @return array
     */
    protected function prepareResponse(Sale $sale, Buyer $buyer, Car $car, array $financialMetrics): array
    {
        $saleWithRelations = $sale->load(['car', 'car.make', 'car.model', 'buyer']);
        $carWithRelations = $car->load(['make', 'model']);

        return [
            'message' => 'Car sold successfully with integrated buyer creation',
            'sale' => $saleWithRelations,
            'buyer' => $buyer,
            'car' => $carWithRelations,
            'financial_summary' => [
                'sale_price' => (float)$sale->sale_price,
                'purchase_cost' => (float)$financialMetrics['purchase_cost'],
                'profit_loss' => (float)$financialMetrics['profit_loss'],
                'profit_margin' => $financialMetrics['profit_margin'],
                'cost_breakdown' => [
                    'base_cost' => (float)$car->cost_price,
                    'transition_cost' => (float)($car->transition_cost ?? 0),
                    'repair_cost' => (float)$financialMetrics['total_repair_cost'],
                    'total_purchase_cost' => (float)$financialMetrics['purchase_cost'],
                ],
                'repair_items' => is_array($car->repair_items) ? $car->repair_items : json_decode($car->repair_items, true) ?? [],
            ],
            'metadata' => [
                'transaction_id' => $sale->id,
                'buyer_created' => true,
                'reports_generated' => true,
                'car_status_updated' => true,
                'timestamp' => now()->toISOString(),
            ]
        ];
    }
} 