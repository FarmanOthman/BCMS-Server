<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Make;
use Illuminate\Support\Str;

class MakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $makes = [
            'Toyota', 'Honda', 'Ford', 'Chevrolet', 'Nissan',
            'BMW', 'Mercedes-Benz', 'Audi', 'Volkswagen', 'Hyundai',
            'Kia', 'Mazda', 'Subaru', 'Lexus', 'Jeep',
            // Add more makes as needed
        ];

        foreach ($makes as $makeName) {
            Make::firstOrCreate(['name' => $makeName]);
        }
    }
}
