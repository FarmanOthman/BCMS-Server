<?php

declare(strict_types=1);

/**
 * Feature Test: Daily Sales Report Command
 * 
 * Purpose:
 * This test specifically verifies the functionality of the 'reports:generate-daily' Artisan command.
 * It tests that the command correctly generates daily sales reports from sales data.
 * 
 * What it tests:
 * - Command generates an empty report when no sales exist for a given date
 * - Command correctly aggregates sales data for a specific date
 * - Command properly calculates total sales, revenue, profit, averages and identifies most profitable car
 * - Command ignores sales from other dates
 * 
 * Usage:
 * Run this test when making changes to the daily report generation command to ensure
 * it continues to work correctly with the application's data layer.
 * 
 * Command tested: 'reports:generate-daily'
 */

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\DailySalesReport;
use App\Models\Make;
use App\Models\Model as CarModel;
use App\Models\Car;
use App\Models\Buyer;
use App\Models\Sale;
use App\Models\User; // Added User model

uses(TestCase::class, RefreshDatabase::class);

it('generates empty daily report when no sales for the given date', function () {
    $date = '2025-06-14';
    Artisan::call('reports:generate-daily', ['date' => $date]);

    $report = DailySalesReport::find($date);
    expect($report)->not->toBeNull()
        ->and($report->total_sales)->toBe(0)
        ->and($report->total_revenue)->toBe('0.00')
        ->and($report->total_profit)->toBe('0.00')
        ->and($report->avg_profit_per_sale)->toBe('0.00')
        ->and($report->most_profitable_car_id)->toBeNull()
        ->and($report->highest_single_profit)->toBe('0.00');
});

it('generates report with correct aggregates when sales exist for the date', function () {
    $date = '2025-06-14';

    // Create a single user to be used for all records needing a user ID
    $user = User::factory()->create(['email' => 'testuser_'.Str::random(10).'@example.com']);

    // Seed Make & Model (can be reused or create new ones if needed for differentiation)
    $make = Make::factory()->create(['name' => 'TestMake']);
    $model = CarModel::factory()->create(['make_id' => $make->id, 'name' => 'TestModel']);
    $model2 = CarModel::factory()->create(['make_id' => $make->id, 'name' => 'TestModelX']);


    // --- Sale 1 Data ---
    $car1 = Car::factory()->create([
        'make_id' => $make->id,
        'model_id' => $model->id,
        'vin' => Str::random(17) . '_C1', // Ensure unique VIN
        'year' => 2023,
        'cost_price' => 10000.00,
        'transition_cost' => 500.00,
        'total_repair_cost' => 200.00,
        'status' => 'available',
    ]);
    $buyer1 = Buyer::factory()->create();

    $purchaseCostCar1 = $car1->cost_price + $car1->transition_cost + $car1->total_repair_cost;
    $salePriceCar1 = 15000.00;
    $profitCar1 = $salePriceCar1 - $purchaseCostCar1;

    Sale::factory()->create([
        'car_id' => $car1->id,
        'buyer_id' => $buyer1->id,
        'sale_price' => $salePriceCar1,
        'purchase_cost' => $purchaseCostCar1,
        'profit_loss' => $profitCar1,
        'sale_date' => $date,
        'created_by' => $user->id, // Assign the created user
        'updated_by' => $user->id, // Assign the created user
    ]);

    // --- Sale 2 Data ---
    $car2 = Car::factory()->create([
        'make_id' => $make->id,
        'model_id' => $model2->id, // Using a different model for variety
        'vin' => Str::random(17) . '_C2', // Ensure unique VIN
        'year' => 2022,
        'cost_price' => 8000.00,
        'transition_cost' => 400.00,
        'total_repair_cost' => 100.00,
        'status' => 'available',
    ]);
    $buyer2 = Buyer::factory()->create();
    $purchaseCostCar2 = $car2->cost_price + $car2->transition_cost + $car2->total_repair_cost;
    $salePriceCar2 = 10000.00; // Lower sale price, potentially smaller profit
    $profitCar2 = $salePriceCar2 - $purchaseCostCar2;

    Sale::factory()->create([
        'car_id' => $car2->id,
        'buyer_id' => $buyer2->id,
        'sale_price' => $salePriceCar2,
        'purchase_cost' => $purchaseCostCar2,
        'profit_loss' => $profitCar2,
        'sale_date' => $date,
        'created_by' => $user->id, // Assign the created user
        'updated_by' => $user->id, // Assign the created user
    ]);
    
    // --- Sale 3 Data (Different Date - Should NOT be included in report) ---
    $car3 = Car::factory()->create([
        'make_id' => $make->id,
        'model_id' => $model->id,
        'vin' => Str::random(17) . '_C3',
        'year' => 2024,
        'cost_price' => 12000.00,
        'transition_cost' => 600.00,
        'total_repair_cost' => 300.00,
        'status' => 'available',
    ]);
    $buyer3 = Buyer::factory()->create();
    $purchaseCostCar3 = $car3->cost_price + $car3->transition_cost + $car3->total_repair_cost;
    $salePriceCar3 = 18000.00;
    $profitCar3 = $salePriceCar3 - $purchaseCostCar3;

    Sale::factory()->create([
        'car_id' => $car3->id,
        'buyer_id' => $buyer3->id,
        'sale_price' => $salePriceCar3,
        'purchase_cost' => $purchaseCostCar3,
        'profit_loss' => $profitCar3,
        'sale_date' => '2025-06-15', // Different date
        'created_by' => $user->id, // Assign the created user
        'updated_by' => $user->id, // Assign the created user
    ]);


    // --- Expected Aggregates ---
    $expectedTotalSales = 2;
    $expectedTotalRevenue = $salePriceCar1 + $salePriceCar2;
    $expectedTotalProfit = $profitCar1 + $profitCar2;
    $expectedAvgProfitPerSale = $expectedTotalProfit / $expectedTotalSales;
    
    $expectedMostProfitableCarId = $profitCar1 >= $profitCar2 ? (string) $car1->id : (string) $car2->id;
    $expectedHighestSingleProfit = max($profitCar1, $profitCar2);

    Artisan::call('reports:generate-daily', ['date' => $date]);
    $report = DailySalesReport::find($date);

    expect($report)->not->toBeNull()
        ->and($report->total_sales)->toBe($expectedTotalSales)
        ->and($report->total_revenue)->toBe(number_format($expectedTotalRevenue, 2, '.', ''))
        ->and($report->total_profit)->toBe(number_format($expectedTotalProfit, 2, '.', ''))
        ->and($report->avg_profit_per_sale)->toBe(number_format($expectedAvgProfitPerSale, 2, '.', ''))
        ->and($report->most_profitable_car_id)->toBe($expectedMostProfitableCarId)
        ->and($report->highest_single_profit)->toBe(number_format($expectedHighestSingleProfit, 2, '.', ''));
});
