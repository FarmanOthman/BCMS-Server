<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Make;
use App\Models\Model;
use App\Models\Car;
use App\Models\Buyer;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users if they don't exist
        $this->createTestUsers();
        
        // Create makes and models
        $this->createMakesAndModels();
        
        // Create test cars
        $this->createTestCars();
        
        // Create test buyers
        $this->createTestBuyers();
    }

    private function createTestUsers()
    {
        // Create manager user
        User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Test Manager',
                'email' => 'manager@example.com',
                'password' => bcrypt('password123'),
                'role' => 'Manager',
            ]
        );

        // Create regular user
        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Test User',
                'email' => 'user@example.com',
                'password' => bcrypt('password123'),
                'role' => 'User',
            ]
        );
    }

    private function createMakesAndModels()
    {
        // Create makes
        $toyota = Make::firstOrCreate(
            ['name' => 'Toyota'],
            ['id' => Str::uuid()]
        );

        $honda = Make::firstOrCreate(
            ['name' => 'Honda'],
            ['id' => Str::uuid()]
        );

        $ford = Make::firstOrCreate(
            ['name' => 'Ford'],
            ['id' => Str::uuid()]
        );

        $bmw = Make::firstOrCreate(
            ['name' => 'BMW'],
            ['id' => Str::uuid()]
        );

        $mercedes = Make::firstOrCreate(
            ['name' => 'Mercedes'],
            ['id' => Str::uuid()]
        );

        // Create models for Toyota
        Model::firstOrCreate(
            ['name' => 'Camry', 'make_id' => $toyota->id],
            ['id' => Str::uuid()]
        );

        Model::firstOrCreate(
            ['name' => 'Corolla', 'make_id' => $toyota->id],
            ['id' => Str::uuid()]
        );

        // Create models for Honda
        Model::firstOrCreate(
            ['name' => 'Civic', 'make_id' => $honda->id],
            ['id' => Str::uuid()]
        );

        Model::firstOrCreate(
            ['name' => 'Accord', 'make_id' => $honda->id],
            ['id' => Str::uuid()]
        );

        // Create models for Ford
        Model::firstOrCreate(
            ['name' => 'Focus', 'make_id' => $ford->id],
            ['id' => Str::uuid()]
        );

        Model::firstOrCreate(
            ['name' => 'F-150', 'make_id' => $ford->id],
            ['id' => Str::uuid()]
        );

        // Create models for BMW
        Model::firstOrCreate(
            ['name' => '3 Series', 'make_id' => $bmw->id],
            ['id' => Str::uuid()]
        );

        Model::firstOrCreate(
            ['name' => '5 Series', 'make_id' => $bmw->id],
            ['id' => Str::uuid()]
        );

        // Create models for Mercedes
        Model::firstOrCreate(
            ['name' => 'C-Class', 'make_id' => $mercedes->id],
            ['id' => Str::uuid()]
        );

        Model::firstOrCreate(
            ['name' => 'E-Class', 'make_id' => $mercedes->id],
            ['id' => Str::uuid()]
        );
    }

    private function createTestCars()
    {
        $manager = User::where('email', 'manager@example.com')->first();
        
        // Get makes and models
        $toyota = Make::where('name', 'Toyota')->first();
        $honda = Make::where('name', 'Honda')->first();
        $ford = Make::where('name', 'Ford')->first();
        $bmw = Make::where('name', 'BMW')->first();
        $mercedes = Make::where('name', 'Mercedes')->first();

        $camry = Model::where('name', 'Camry')->where('make_id', $toyota->id)->first();
        $civic = Model::where('name', 'Civic')->where('make_id', $honda->id)->first();
        $focus = Model::where('name', 'Focus')->where('make_id', $ford->id)->first();
        $bmw3 = Model::where('name', '3 Series')->where('make_id', $bmw->id)->first();
        $cClass = Model::where('name', 'C-Class')->where('make_id', $mercedes->id)->first();

        // Create test cars
        $testCars = [
            [
                'make_id' => $toyota->id,
                'model_id' => $camry->id,
                'year' => 2022,
                'vin' => 'TEST001',
                'cost_price' => 25000.00,
                'transition_cost' => 1200.00,
                'total_repair_cost' => 800.00,
                'selling_price' => 28000.00,
                'public_price' => 30000.00,
                'status' => 'available',
                'repair_items' => json_encode([
                    ['description' => 'Paint touch-up', 'cost' => 300.00],
                    ['description' => 'Interior cleaning', 'cost' => 200.00],
                    ['description' => 'Engine tune-up', 'cost' => 300.00]
                ]),
                'created_by' => $manager->id,
                'updated_by' => $manager->id,
            ],
            [
                'make_id' => $honda->id,
                'model_id' => $civic->id,
                'year' => 2023,
                'vin' => 'TEST002',
                'cost_price' => 22000.00,
                'transition_cost' => 900.00,
                'total_repair_cost' => 500.00,
                'selling_price' => 24000.00,
                'public_price' => 26000.00,
                'status' => 'available',
                'repair_items' => json_encode([
                    ['description' => 'Tire replacement', 'cost' => 400.00],
                    ['description' => 'Brake inspection', 'cost' => 100.00]
                ]),
                'created_by' => $manager->id,
                'updated_by' => $manager->id,
            ],
            [
                'make_id' => $ford->id,
                'model_id' => $focus->id,
                'year' => 2021,
                'vin' => 'TEST003',
                'cost_price' => 18000.00,
                'transition_cost' => 700.00,
                'total_repair_cost' => 1200.00,
                'selling_price' => 20000.00,
                'public_price' => 22000.00,
                'status' => 'available',
                'repair_items' => json_encode([
                    ['description' => 'Transmission repair', 'cost' => 800.00],
                    ['description' => 'New tires', 'cost' => 400.00]
                ]),
                'created_by' => $manager->id,
                'updated_by' => $manager->id,
            ],
            [
                'make_id' => $bmw->id,
                'model_id' => $bmw3->id,
                'year' => 2022,
                'vin' => 'TEST004',
                'cost_price' => 35000.00,
                'transition_cost' => 1500.00,
                'total_repair_cost' => 2000.00,
                'selling_price' => 38000.00,
                'public_price' => 42000.00,
                'status' => 'available',
                'repair_items' => json_encode([
                    ['description' => 'Premium detailing', 'cost' => 800.00],
                    ['description' => 'Navigation system', 'cost' => 1200.00]
                ]),
                'created_by' => $manager->id,
                'updated_by' => $manager->id,
            ],
            [
                'make_id' => $mercedes->id,
                'model_id' => $cClass->id,
                'year' => 2023,
                'vin' => 'TEST005',
                'cost_price' => 40000.00,
                'transition_cost' => 1800.00,
                'total_repair_cost' => 2500.00,
                'selling_price' => 43000.00,
                'public_price' => 48000.00,
                'status' => 'available',
                'repair_items' => json_encode([
                    ['description' => 'Luxury interior upgrade', 'cost' => 1500.00],
                    ['description' => 'Advanced safety features', 'cost' => 1000.00]
                ]),
                'created_by' => $manager->id,
                'updated_by' => $manager->id,
            ],
        ];

        foreach ($testCars as $carData) {
            Car::firstOrCreate(
                ['vin' => $carData['vin']],
                array_merge($carData, ['id' => Str::uuid()])
            );
        }
    }

    private function createTestBuyers()
    {
        $testBuyers = [
            [
                'name' => 'John Doe',
                'phone' => '555-123-4567',
                'address' => '123 Main St, City, State 12345',
            ],
            [
                'name' => 'Jane Smith',
                'phone' => '555-234-5678',
                'address' => '456 Oak Ave, Town, State 23456',
            ],
            [
                'name' => 'Robert Johnson',
                'phone' => '555-345-6789',
                'address' => '789 Pine Rd, Village, State 34567',
            ],
            [
                'name' => 'Sarah Williams',
                'phone' => '555-456-7890',
                'address' => '101 Maple Ln, County, State 45678',
            ],
            [
                'name' => 'Michael Brown',
                'phone' => '555-567-8901',
                'address' => '202 Cedar Dr, Metro, State 56789',
            ],
        ];

        foreach ($testBuyers as $buyerData) {
            Buyer::firstOrCreate(
                ['phone' => $buyerData['phone']],
                array_merge($buyerData, ['id' => Str::uuid()])
            );
        }
    }
} 