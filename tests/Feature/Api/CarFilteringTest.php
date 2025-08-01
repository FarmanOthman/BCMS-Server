<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Car;
use App\Models\Make;
use App\Models\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CarFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manager;
    protected $make;
    protected $model;
    protected $userToken;
    protected $managerToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create(['role' => 'User']);
        $this->manager = User::factory()->create(['role' => 'Manager']);

        // Create test make and model
        $this->make = Make::factory()->create(['name' => 'Toyota']);
        $this->model = Model::factory()->create([
            'name' => 'Camry',
            'make_id' => $this->make->id
        ]);

        // Create proper tokens for authentication (same as existing tests)
        $this->userToken = base64_encode(json_encode([
            'user_id' => $this->user->id,
            'exp' => time() + 3600
        ]));
        
        $this->managerToken = base64_encode(json_encode([
            'user_id' => $this->manager->id,
            'exp' => time() + 3600
        ]));

        // Create test cars with different years
        Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2020,
            'status' => 'available'
        ]);

        Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2021,
            'status' => 'available'
        ]);

        Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2022,
            'status' => 'available'
        ]);
    }

    public function test_public_car_listing_supports_make_filtering()
    {
        $response = $this->getJson("/bcms/cars?make_id={$this->make->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'make_id', 'model_id', 'year', 'total', 'pages']
            ]);

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals($this->make->id, $response->json('meta.make_id'));
    }

    public function test_public_car_listing_supports_year_filtering()
    {
        $response = $this->getJson("/bcms/cars?year=2020");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'make_id', 'model_id', 'year', 'total', 'pages']
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(2020, $response->json('meta.year'));
        $this->assertEquals(2020, $response->json('data.0.year'));
    }

    public function test_public_car_listing_supports_model_filtering()
    {
        $response = $this->getJson("/bcms/cars?model_id={$this->model->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'make_id', 'model_id', 'year', 'total', 'pages']
            ]);

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals($this->model->id, $response->json('meta.model_id'));
    }

    public function test_public_car_listing_supports_combined_filtering()
    {
        $response = $this->getJson("/bcms/cars?make_id={$this->make->id}&year=2020");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'make_id', 'model_id', 'year', 'total', 'pages']
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->make->id, $response->json('meta.make_id'));
        $this->assertEquals(2020, $response->json('meta.year'));
    }

    public function test_admin_car_listing_supports_make_filtering()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson("/bcms/admin/cars?make_id={$this->make->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'make_id', 'model_id', 'year', 'total', 'pages']
            ]);

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals($this->make->id, $response->json('meta.make_id'));
    }

    public function test_admin_car_listing_supports_year_filtering()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson("/bcms/admin/cars?year=2021");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'make_id', 'model_id', 'year', 'total', 'pages']
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(2021, $response->json('meta.year'));
        $this->assertEquals(2021, $response->json('data.0.year'));
    }

    public function test_admin_car_listing_supports_combined_filtering()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson("/bcms/admin/cars?make_id={$this->make->id}&model_id={$this->model->id}&year=2022");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'make_id', 'model_id', 'year', 'total', 'pages']
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->make->id, $response->json('meta.make_id'));
        $this->assertEquals($this->model->id, $response->json('meta.model_id'));
        $this->assertEquals(2022, $response->json('meta.year'));
    }

    public function test_pagination_works_with_filters()
    {
        // Create more cars to test pagination
        for ($i = 0; $i < 5; $i++) {
            Car::factory()->create([
                'make_id' => $this->make->id,
                'model_id' => $this->model->id,
                'year' => 2020,
                'status' => 'available'
            ]);
        }

        $response = $this->getJson("/bcms/cars?make_id={$this->make->id}&year=2020&limit=3&page=1");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'make_id', 'model_id', 'year', 'total', 'pages']
            ]);

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals(1, $response->json('meta.page'));
        $this->assertEquals(3, $response->json('meta.limit'));
        $this->assertEquals(6, $response->json('meta.total')); // 1 original + 5 new cars
        $this->assertEquals(2, $response->json('meta.pages')); // ceil(6/3) = 2
    }

    public function test_public_endpoint_only_shows_available_cars()
    {
        // Create a sold car
        Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2020,
            'status' => 'sold'
        ]);

        $response = $this->getJson("/bcms/cars?make_id={$this->make->id}&year=2020");

        $response->assertStatus(200);
        
        // Should only show available cars, not the sold one
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('available', $response->json('data.0.status'));
    }

    public function test_admin_endpoint_shows_all_cars_including_sold()
    {
        // Create a sold car
        Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2020,
            'status' => 'sold'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson("/bcms/admin/cars?make_id={$this->make->id}&year=2020");

        $response->assertStatus(200);
        
        // Should show both available and sold cars
        $this->assertCount(2, $response->json('data'));
        
        $statuses = collect($response->json('data'))->pluck('status')->toArray();
        $this->assertContains('available', $statuses);
        $this->assertContains('sold', $statuses);
    }
} 