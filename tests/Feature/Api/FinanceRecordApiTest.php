<?php

namespace Tests\Feature\Api;

use App\Models\FinanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class FinanceRecordApiTest extends TestCase
{
    use RefreshDatabase;

    protected $managerToken = 'manager-test-token';
    protected $userToken = 'user-test-token';
    protected $manager;
    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->manager = User::factory()->create([
            'email' => 'finance-test-manager@example.com',
            'role' => 'Manager',
            'name' => 'Finance Test Manager'
        ]);
        
        $this->user = User::factory()->create([
            'email' => 'finance-test-user@example.com', 
            'role' => 'User',
            'name' => 'Finance Test User'
        ]);
        
        // Setup the mock for the manager token
        $this->actingAs($this->manager);
        
        // Setup the mock for the user token
        $this->actingAs($this->user);
        
        // Setup the mock for invalid tokens
        $this->actingAs(null);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_manager_can_get_all_finance_records()
    {
        // Create test finance records
        FinanceRecord::factory()->count(3)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/finance-records');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['page', 'limit', 'total', 'pages']
                 ]);
        
        $this->assertCount(3, $response->json('data'));
    }
      public function test_manager_can_create_finance_record()
    {
        // Login the manager
        $this->actingAs($this->manager);
          $recordData = [
            'type' => 'income',
            'category' => 'sale',
            'cost' => 5000,
            'record_date' => now()->format('Y-m-d'),
            'description' => 'Test finance record'
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/finance-records', $recordData);$response->assertStatus(201)
                 ->assertJsonFragment([
                     'type' => 'income',
                     'category' => 'sale',
                     'cost' => '5000.00',
                     'description' => 'Test finance record'
                 ]);
    }
    
    public function test_manager_can_get_single_finance_record()
    {
        $record = FinanceRecord::factory()->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/finance-records/' . $record->id);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $record->id,
                     'type' => $record->type,
                     'category' => $record->category,
                 ]);
    }
    
    public function test_manager_can_update_finance_record()
    {
        // Login the manager
        $this->actingAs($this->manager);
          $record = FinanceRecord::factory()->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
            'type' => 'expense',
            'category' => 'repair',
            'cost' => 2000
        ]);
        
        $updatedData = [
            'cost' => 2500,
            'description' => 'Updated finance record description'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/finance-records/' . $record->id, $updatedData);        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'cost' => '2500.00',
                     'description' => 'Updated finance record description'
                 ]);
    }
    
    public function test_manager_can_delete_finance_record()
    {
        // Login the manager
        $this->actingAs($this->manager);
        
        $record = FinanceRecord::factory()->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/finance-records/' . $record->id);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Finance record deleted successfully'
                 ]);
        
        $this->assertDatabaseMissing('financerecord', ['id' => $record->id]);
    }
    
    public function test_finance_record_validation_rules()
    {        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/finance-records', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'type', 'category', 'cost', 'record_date'
                 ]);
          $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/finance-records', [            
            'type' => '',
            'category' => '',
            'cost' => 'not-a-number',
            'record_date' => 'not-a-date'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'type', 'category', 'cost', 'record_date'
                 ]);
          // Test negative amount validation
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/finance-records', [            
            'type' => 'income',
            'category' => 'sale',
            'cost' => -100,
            'record_date' => now()->format('Y-m-d')
        ]);$response->assertStatus(422)
                 ->assertJsonValidationErrors(['cost']);
    }
    
    public function test_regular_user_can_access_finance_records_with_appropriate_permissions()
    {
        // This test assumes User role might have some access to finance records
        // You may need to adjust this based on your actual authorization rules
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/finance-records');

        // Depending on your authorization logic, you may expect 200 or 403
        // If Users should not access finance records at all:
        $response->assertStatus(403);
        
        // If Users should have read-only access:
        // $response->assertStatus(200);
    }
    
    public function test_unauthenticated_user_cannot_access_finance_records()
    {
        $response = $this->getJson('/bcms/finance-records');
        $response->assertStatus(401);        $recordData = [
            'type' => 'income',
            'category' => 'sale',
            'cost' => 5000,
            'record_date' => now()->format('Y-m-d'),
            'description' => 'Test finance record'
        ];

        $response = $this->postJson('/bcms/finance-records', $recordData);
        $response->assertStatus(401);
    }
      public function test_finance_filters_work_correctly()
    {
        // Create finance records with different attributes for testing filters
        FinanceRecord::factory()->income()->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
            'category' => 'sale',
            'cost' => 10000,
            'created_at' => '2025-01-15'
        ]);
        
        FinanceRecord::factory()->expense()->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
            'category' => 'repair',
            'cost' => 5000,
            'created_at' => '2025-02-20'
        ]);
        
        FinanceRecord::factory()->income()->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
            'category' => 'investment',
            'cost' => 20000,
            'created_at' => '2025-03-25'
        ]);
        
        // Test type filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/finance-records?type=income');
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        // Test category filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/finance-records?category=sale');
        
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        
        // Test date range filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/finance-records?date_from=2025-02-01&date_to=2025-03-31');
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
    
    public function test_finance_calculation_accuracy()
    {
        // This test ensures that financial calculations are accurate
        // and consistent with business requirements
          // Create a set of income records       
        $incomeRecords = [
            ['type' => 'income', 'category' => 'sale', 'cost' => 10000, 'record_date' => now()->format('Y-m-d')],
            ['type' => 'income', 'category' => 'sale', 'cost' => 15000, 'record_date' => now()->format('Y-m-d')],
            ['type' => 'income', 'category' => 'investment', 'cost' => 5000, 'record_date' => now()->format('Y-m-d')],
        ];
        
        // Create a set of expense records
        $expenseRecords = [
            ['type' => 'expense', 'category' => 'repair', 'cost' => 3000, 'record_date' => now()->format('Y-m-d')],
            ['type' => 'expense', 'category' => 'purchase', 'cost' => 8000, 'record_date' => now()->format('Y-m-d')],
            ['type' => 'expense', 'category' => 'utilities', 'cost' => 2000, 'record_date' => now()->format('Y-m-d')],
        ];
        
        // Login the manager and create all records
        $this->actingAs($this->manager);
        
        foreach ($incomeRecords as $record) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->managerToken,
            ])->postJson('/bcms/finance-records', $record);
        }
        
        foreach ($expenseRecords as $record) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->managerToken,
            ])->postJson('/bcms/finance-records', $record);
        }
        
        // Verify records were created correctly
        $this->assertDatabaseCount('financerecord', 6);
          // Manually calculate the expected totals
        $expectedTotalIncome = array_sum(array_column($incomeRecords, 'cost')); // 30000
        $expectedTotalExpense = array_sum(array_column($expenseRecords, 'cost')); // 13000
        $expectedNetProfit = $expectedTotalIncome - $expectedTotalExpense; // 17000
        
        // Verify income total
        $incomeTotal = FinanceRecord::where('type', 'income')->sum('cost');
        $this->assertEquals($expectedTotalIncome, $incomeTotal);
        
        // Verify expense total
        $expenseTotal = FinanceRecord::where('type', 'expense')->sum('cost');
        $this->assertEquals($expectedTotalExpense, $expenseTotal);
          // Verify net profit calculation
        $netProfit = $incomeTotal - $expenseTotal;
        $this->assertEquals($expectedNetProfit, $netProfit);
    }
}
