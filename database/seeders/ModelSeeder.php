<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Make;
use App\Models\Model as CarModel; // Alias to avoid conflict
use Illuminate\Support\Str;

class ModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modelsByMake = [
            'Toyota' => ['Camry', 'Corolla', 'Rav4', 'Highlander', 'Tacoma'],
            'Honda' => ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey'],
            'Ford' => ['F-150', 'Explorer', 'Escape', 'Mustang', 'Ranger'],
            'Chevrolet' => ['Silverado', 'Equinox', 'Tahoe', 'Malibu', 'Traverse'],
            'Nissan' => ['Altima', 'Rogue', 'Sentra', 'Pathfinder', 'Frontier'],
            'BMW' => ['3 Series', '5 Series', 'X3', 'X5', '7 Series'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'GLC', 'GLE', 'S-Class'],
            'Audi' => ['A4', 'A6', 'Q5', 'Q7', 'A3'],
            'Volkswagen' => ['Jetta', 'Tiguan', 'Atlas', 'Golf', 'Passat'],
            // Add more makes and their models as needed
        ];

        foreach ($modelsByMake as $makeName => $models) {
            $make = Make::where('name', $makeName)->first();
            if ($make) {
                foreach ($models as $modelName) {
                    CarModel::firstOrCreate([
                        'make_id' => $make->id,
                        'name' => $modelName,
                    ]);
                }
            }
        }
    }
}
