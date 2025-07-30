<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\TestUsersSeeder;
use Database\Seeders\TestDataSeeder;
use Database\Seeders\CompleteTestSeeder;

class SeedTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:test-data {--users-only : Seed only test users} {--complete : Seed complete test data with additional scenarios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed test data for Postman API testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding test data for Postman API testing...');

        if ($this->option('users-only')) {
            $this->info('Seeding test users only...');
            $this->call(TestUsersSeeder::class);
        } elseif ($this->option('complete')) {
            $this->info('Seeding complete test data with additional scenarios...');
            $this->call(CompleteTestSeeder::class);
        } else {
            $this->info('Seeding test users...');
            $this->call(TestUsersSeeder::class);
            
            $this->info('Seeding test data...');
            $this->call(TestDataSeeder::class);
        }

        $this->info('Test data seeding completed!');
        $this->info('');
        $this->info('Test Users:');
        $this->info('- Manager: manager@example.com / password123');
        $this->info('- User: user@example.com / password123');
        $this->info('');
        $this->info('You can now use these credentials in your Postman environment variables.');
    }
} 