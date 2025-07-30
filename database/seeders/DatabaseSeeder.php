<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            TestUsersSeeder::class, // Add test users for Postman testing
            TestDataSeeder::class, // Add test data for all resources
            MakeSeeder::class,
            ModelSeeder::class,
            // Add other seeders here if needed
        ]);
    }
}
