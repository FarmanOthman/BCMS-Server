<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test makes (using firstOrCreate to avoid conflicts)
        $toyotaId = DB::table('makes')->where('name', 'Toyota')->first()->id ?? 
                   DB::table('makes')->insertGetId([
                       'id' => (string) Str::uuid(),
                       'name' => 'Toyota',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

        $hondaId = DB::table('makes')->where('name', 'Honda')->first()->id ?? 
                  DB::table('makes')->insertGetId([
                      'id' => (string) Str::uuid(),
                      'name' => 'Honda',
                      'created_at' => now(),
                      'updated_at' => now(),
                  ]);

        $fordId = DB::table('makes')->where('name', 'Ford')->first()->id ?? 
                 DB::table('makes')->insertGetId([
                     'id' => (string) Str::uuid(),
                     'name' => 'Ford',
                     'created_at' => now(),
                     'updated_at' => now(),
                 ]);

        // Create test models (using firstOrCreate to avoid conflicts)
        $camryId = DB::table('models')->where('name', 'Camry')->where('make_id', $toyotaId)->first()->id ?? 
                  DB::table('models')->insertGetId([
                      'id' => (string) Str::uuid(),
                      'make_id' => $toyotaId,
                      'name' => 'Camry',
                      'created_at' => now(),
                      'updated_at' => now(),
                  ]);

        $civicId = DB::table('models')->where('name', 'Civic')->where('make_id', $hondaId)->first()->id ?? 
                  DB::table('models')->insertGetId([
                      'id' => (string) Str::uuid(),
                      'make_id' => $hondaId,
                      'name' => 'Civic',
                      'created_at' => now(),
                      'updated_at' => now(),
                  ]);

        $focusId = DB::table('models')->where('name', 'Focus')->where('make_id', $fordId)->first()->id ?? 
                  DB::table('models')->insertGetId([
                      'id' => (string) Str::uuid(),
                      'make_id' => $fordId,
                      'name' => 'Focus',
                      'created_at' => now(),
                      'updated_at' => now(),
                  ]);

        // Create test buyers (check if they exist first)
        $buyer1Id = DB::table('buyer')->where('name', 'John Doe')->where('phone', '+1234567890')->first()->id ?? 
                   DB::table('buyer')->insertGetId([
                       'id' => (string) Str::uuid(),
                       'name' => 'John Doe',
                       'phone' => '+1234567890',
                       'address' => '123 Main St, City, State',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

        $buyer2Id = DB::table('buyer')->where('name', 'Jane Smith')->where('phone', '+1234567891')->first()->id ?? 
                   DB::table('buyer')->insertGetId([
                       'id' => (string) Str::uuid(),
                       'name' => 'Jane Smith',
                       'phone' => '+1234567891',
                       'address' => '456 Oak Ave, City, State',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

        // Create test cars (check if they exist first)
        $car1Id = DB::table('cars')->where('make_id', $toyotaId)->where('model_id', $camryId)->where('year', 2020)->first()->id ?? 
                 DB::table('cars')->insertGetId([
                     'id' => (string) Str::uuid(),
                     'make_id' => $toyotaId,
                     'model_id' => $camryId,
                     'year' => 2020,
                     'vin' => '1HGBH41JXMN109186',
                     'cost_price' => 22000,
                     'transition_cost' => 1000,
                     'total_repair_cost' => 0,
                     'selling_price' => 25000,
                     'public_price' => 27000,
                     'status' => 'available',
                     'repair_items' => null,
                     'created_at' => now(),
                     'updated_at' => now(),
                 ]);

        $car2Id = DB::table('cars')->where('make_id', $hondaId)->where('model_id', $civicId)->where('year', 2019)->first()->id ?? 
                 DB::table('cars')->insertGetId([
                     'id' => (string) Str::uuid(),
                     'make_id' => $hondaId,
                     'model_id' => $civicId,
                     'year' => 2019,
                     'vin' => '2T1BURHE0JC123456',
                     'cost_price' => 18000,
                     'transition_cost' => 800,
                     'total_repair_cost' => 500,
                     'selling_price' => 20000,
                     'public_price' => 22000,
                     'status' => 'available',
                     'repair_items' => '["Oil change", "brake pads"]',
                     'created_at' => now(),
                     'updated_at' => now(),
                 ]);

        $car3Id = DB::table('cars')->where('make_id', $fordId)->where('model_id', $focusId)->where('year', 2021)->first()->id ?? 
                 DB::table('cars')->insertGetId([
                     'id' => (string) Str::uuid(),
                     'make_id' => $fordId,
                     'model_id' => $focusId,
                     'year' => 2021,
                     'vin' => '3VWDX7AJ5DM123789',
                     'cost_price' => 16000,
                     'transition_cost' => 600,
                     'total_repair_cost' => 0,
                     'selling_price' => 18000,
                     'public_price' => 20000,
                     'status' => 'sold',
                     'repair_items' => null,
                     'created_at' => now(),
                     'updated_at' => now(),
                 ]);

        // Create test sales (check if they exist first)
        $existingSale = DB::table('sale')->where('car_id', $car3Id)->first();
        if (!$existingSale) {
            DB::table('sale')->insert([
                'id' => (string) Str::uuid(),
                'car_id' => $car3Id,
                'buyer_id' => $buyer1Id,
                'sale_price' => 19500,
                'purchase_cost' => 18000,
                'profit_loss' => 1500,
                'sale_date' => '2024-01-10',
                'notes' => 'Cash sale',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create test finance records (check if they exist first)
        $existingFinance1 = DB::table('financerecord')->where('description', 'Office rent')->first();
        if (!$existingFinance1) {
            DB::table('financerecord')->insert([
                'id' => (string) Str::uuid(),
                'description' => 'Office rent',
                'cost' => 2000,
                'type' => 'expense',
                'category' => 'rent',
                'record_date' => '2024-01-15',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $existingFinance2 = DB::table('financerecord')->where('description', 'Car purchase commission')->first();
        if (!$existingFinance2) {
            DB::table('financerecord')->insert([
                'id' => (string) Str::uuid(),
                'description' => 'Car purchase commission',
                'cost' => 500,
                'type' => 'income',
                'category' => 'commission',
                'record_date' => '2024-01-10',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create test daily sales report (check if it exists first)
        $existingDailyReport = DB::table('dailysalesreport')->where('report_date', '2024-01-15')->first();
        if (!$existingDailyReport) {
            DB::table('dailysalesreport')->insert([
                'report_date' => '2024-01-15',
                'total_sales' => 2,
                'total_revenue' => 50000,
                'total_profit' => 5000,
                'avg_profit_per_sale' => 2500,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create test monthly sales report (check if it exists first)
        $existingMonthlyReport = DB::table('monthlysalesreport')->where('year', 2024)->where('month', 1)->first();
        if (!$existingMonthlyReport) {
            DB::table('monthlysalesreport')->insert([
                'year' => 2024,
                'month' => 1,
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'total_sales' => 6,
                'total_revenue' => 150000,
                'total_profit' => 15000,
                'avg_daily_profit' => 500,
                'finance_cost' => 5000,
                'net_profit' => 10000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create test yearly sales report (check if it exists first)
        $existingYearlyReport = DB::table('yearlysalesreport')->where('year', 2024)->first();
        if (!$existingYearlyReport) {
            DB::table('yearlysalesreport')->insert([
                'year' => 2024,
                'total_sales' => 72,
                'total_revenue' => 1800000,
                'total_profit' => 180000,
                'avg_monthly_profit' => 15000,
                'best_month' => 12,
                'best_month_profit' => 20000,
                'profit_margin' => 10.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($this->command) {
            $this->command->info('Test data created/verified successfully!');
            $this->command->info('Created/Verified:');
            $this->command->info('- Makes (Toyota, Honda, Ford)');
            $this->command->info('- Models (Camry, Civic, Focus)');
            $this->command->info('- 2 Buyers (John Doe, Jane Smith)');
            $this->command->info('- 3 Cars (2 available, 1 sold)');
            $this->command->info('- 1 Sale record');
            $this->command->info('- 2 Finance records');
            $this->command->info('- 1 Daily sales report');
            $this->command->info('- 1 Monthly sales report');
            $this->command->info('- 1 Yearly sales report');
        }
    }
} 