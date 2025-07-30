<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if manager user exists, if not create it
        $managerUser = DB::table('users')->where('email', 'manager@example.com')->first();
        if (!$managerUser) {
            DB::table('users')->insert([
                'id' => (string) Str::uuid(),
                'email' => 'manager@example.com',
                'name' => 'Test Manager',
                'role' => 'Manager',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Check if user exists, if not create it
        $regularUser = DB::table('users')->where('email', 'user@example.com')->first();
        if (!$regularUser) {
            DB::table('users')->insert([
                'id' => (string) Str::uuid(),
                'email' => 'user@example.com',
                'name' => 'Test User',
                'role' => 'User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($this->command) {
            $this->command->info('Test users created/verified successfully!');
            $this->command->info('Manager: manager@example.com / password123');
            $this->command->info('User: user@example.com / password123');
        }
    }
} 