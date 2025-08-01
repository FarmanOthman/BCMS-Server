<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompleteTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Run TestDataSeeder
        $this->call([
            TestDataSeeder::class,
        ]);

        // Add additional test scenarios
        $this->addAdditionalTestData();
    }

    private function addAdditionalTestData()
    {
        // Add more test cars for different scenarios
        $this->addMoreTestCars();
        
        // Add more test buyers
        $this->addMoreTestBuyers();
        
        // Add more test sales
        $this->addMoreTestSales();
        
        // Add more finance records
        $this->addMoreFinanceRecords();
        
        // Add more reports
        $this->addMoreReports();
    }

    private function addMoreTestCars()
    {
        // Get existing makes and models
        $toyota = DB::table('makes')->where('name', 'Toyota')->first();
        $honda = DB::table('makes')->where('name', 'Honda')->first();
        $ford = DB::table('makes')->where('name', 'Ford')->first();
        
        $camry = DB::table('models')->where('name', 'Camry')->where('make_id', $toyota->id)->first();
        $civic = DB::table('models')->where('name', 'Civic')->where('make_id', $honda->id)->first();
        $focus = DB::table('models')->where('name', 'Focus')->where('make_id', $ford->id)->first();

        // Add more cars for testing different scenarios
        $additionalCars = [
            [
                'make_id' => $toyota->id,
                'model_id' => $camry->id,
                'year' => 2021,
                'vin' => '1HGBH41JXMN109187',
                'cost_price' => 25000,
                'transition_cost' => 1200,
                'total_repair_cost' => 0,
                'selling_price' => 28000,
                'public_price' => 30000,
                'status' => 'available',
                'repair_items' => null,
                'color' => 'Silver',
                'mileage' => 45000,
                'description' => 'Well maintained Toyota Camry',
            ],
            [
                'make_id' => $honda->id,
                'model_id' => $civic->id,
                'year' => 2022,
                'vin' => '2T1BURHE0JC123457',
                'cost_price' => 20000,
                'transition_cost' => 900,
                'total_repair_cost' => 0,
                'selling_price' => 22000,
                'public_price' => 24000,
                'status' => 'available',
                'repair_items' => null,
                'color' => 'Blue',
                'mileage' => 32000,
                'description' => 'Excellent condition Honda Civic',
            ],
            [
                'make_id' => $ford->id,
                'model_id' => $focus->id,
                'year' => 2020,
                'vin' => '3VWDX7AJ5DM123790',
                'cost_price' => 14000,
                'transition_cost' => 700,
                'total_repair_cost' => 1200,
                'selling_price' => 16000,
                'public_price' => 18000,
                'status' => 'maintenance',
                'repair_items' => '["Transmission repair", "new tires"]',
                'color' => 'Red',
                'mileage' => 68000,
                'description' => 'Ford Focus in maintenance',
            ],
        ];

        foreach ($additionalCars as $carData) {
            $existingCar = DB::table('cars')
                ->where('make_id', $carData['make_id'])
                ->where('model_id', $carData['model_id'])
                ->where('year', $carData['year'])
                ->first();

            if (!$existingCar) {
                DB::table('cars')->insert(array_merge($carData, [
                    'id' => (string) Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function addMoreTestBuyers()
    {
        $additionalBuyers = [
            [
                'name' => 'Mike Johnson',
                'phone' => '+1234567892',
                'address' => '789 Pine St, City, State',
            ],
            [
                'name' => 'Sarah Wilson',
                'phone' => '+1234567893',
                'address' => '321 Elm St, City, State',
            ],
            [
                'name' => 'David Brown',
                'phone' => '+1234567894',
                'address' => '654 Maple Ave, City, State',
            ],
        ];

        foreach ($additionalBuyers as $buyerData) {
            $existingBuyer = DB::table('buyer')->where('name', $buyerData['name'])->where('phone', $buyerData['phone'])->first();
            if (!$existingBuyer) {
                DB::table('buyer')->insert(array_merge($buyerData, [
                    'id' => (string) Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function addMoreTestSales()
    {
        // Get existing cars and buyers
        $cars = DB::table('cars')->where('status', 'sold')->get();
        $buyers = DB::table('buyer')->get();

        if ($cars->count() > 0 && $buyers->count() > 1) {
            $additionalSales = [
                [
                    'car_id' => $cars->first()->id,
                    'buyer_id' => $buyers->where('name', 'Mike Johnson')->first()->id ?? $buyers->first()->id,
                    'sale_price' => 18500,
                    'purchase_cost' => 18000,
                    'profit_loss' => 500,
                    'sale_date' => '2024-01-12',
                    'notes' => 'Finance sale',
                ],
                [
                    'car_id' => $cars->first()->id,
                    'buyer_id' => $buyers->where('name', 'Sarah Wilson')->first()->id ?? $buyers->first()->id,
                    'sale_price' => 19000,
                    'purchase_cost' => 18000,
                    'profit_loss' => 1000,
                    'sale_date' => '2024-01-14',
                    'notes' => 'Cash sale',
                ],
            ];

            foreach ($additionalSales as $saleData) {
                $existingSale = DB::table('sales')
                    ->where('car_id', $saleData['car_id'])
                    ->where('buyer_id', $saleData['buyer_id'])
                    ->where('sale_date', $saleData['sale_date'])
                    ->first();

                if (!$existingSale) {
                    DB::table('sale')->insert(array_merge($saleData, [
                        'id' => (string) Str::uuid(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        }
    }

    private function addMoreFinanceRecords()
    {
        $additionalFinanceRecords = [
            [
                'description' => 'Employee salary',
                'cost' => 3000,
                'type' => 'expense',
                'category' => 'salary',
                'record_date' => '2024-01-20',
            ],
            [
                'description' => 'Utility bills',
                'cost' => 500,
                'type' => 'expense',
                'category' => 'utilities',
                'record_date' => '2024-01-18',
            ],
            [
                'description' => 'Car sale commission',
                'cost' => 750,
                'type' => 'income',
                'category' => 'commission',
                'record_date' => '2024-01-12',
            ],
            [
                'description' => 'Insurance payment',
                'cost' => 800,
                'type' => 'expense',
                'category' => 'insurance',
                'record_date' => '2024-01-25',
            ],
        ];

        foreach ($additionalFinanceRecords as $record) {
            $existingRecord = DB::table('financerecord')
                ->where('description', $record['description'])
                ->where('record_date', $record['record_date'])
                ->first();

            if (!$existingRecord) {
                DB::table('financerecord')->insert(array_merge($record, [
                    'id' => (string) Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function addMoreReports()
    {
        // Add more daily reports
        $additionalDailyReports = [
            [
                'report_date' => '2024-01-16',
                'total_sales' => 3,
                'total_revenue' => 75000,
                'total_profit' => 7500,
                'avg_profit_per_sale' => 2500,
            ],
            [
                'report_date' => '2024-01-17',
                'total_sales' => 1,
                'total_revenue' => 30000,
                'total_profit' => 3000,
                'avg_profit_per_sale' => 3000,
            ],
        ];

        foreach ($additionalDailyReports as $report) {
            $existingReport = DB::table('dailysalesreport')->where('report_date', $report['report_date'])->first();
            if (!$existingReport) {
                DB::table('dailysalesreport')->insert(array_merge($report, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // Add more monthly reports
        $additionalMonthlyReports = [
            [
                'year' => 2024,
                'month' => 2,
                'start_date' => '2024-02-01',
                'end_date' => '2024-02-29',
                'total_sales' => 8,
                'total_revenue' => 180000,
                'total_profit' => 18000,
                'avg_daily_profit' => 600,
                'finance_cost' => 7000,
                'net_profit' => 11000,
            ],
        ];

        foreach ($additionalMonthlyReports as $report) {
            $existingReport = DB::table('monthlysalesreport')
                ->where('year', $report['year'])
                ->where('month', $report['month'])
                ->first();
            if (!$existingReport) {
                DB::table('monthlysalesreport')->insert(array_merge($report, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
} 