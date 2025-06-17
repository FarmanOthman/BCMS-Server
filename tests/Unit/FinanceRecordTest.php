<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FinanceRecord;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Str;

class FinanceRecordTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for all tests
        $this->user = new User();
        $this->user->id = Str::uuid(); // Explicitly set a UUID
        $this->user->name = 'Test User';
        $this->user->email = 'test_' . time() . '@example.com';
        $this->user->role = 'admin';
        $this->user->save();
    }
    
    protected function tearDown(): void
    {
        // Clean up all created resources
        if ($this->user) {
            $this->user->delete();
        }
        
        parent::tearDown();
    }

    public function test_can_create_finance_record()
    {
        $financeRecord = new FinanceRecord();
        $financeRecord->id = Str::uuid(); // Explicitly set a UUID
        $financeRecord->type = 'expense';
        $financeRecord->category = 'maintenance';
        $financeRecord->cost = 500.00;
        $financeRecord->description = 'Showroom lighting repair';
        $financeRecord->created_by = $this->user->id;
        $financeRecord->updated_by = $this->user->id;
        $financeRecord->save();

        $this->assertInstanceOf(FinanceRecord::class, $financeRecord);
        $this->assertEquals('expense', $financeRecord->type);
        $this->assertEquals('maintenance', $financeRecord->category);
        $this->assertEquals(500.00, $financeRecord->cost);
        $this->assertEquals('Showroom lighting repair', $financeRecord->description);
        
        // Clean up
        $financeRecord->delete();
    }

    public function test_fillable_attributes()
    {
        $financeRecord = new FinanceRecord();
        
        $fillable = $financeRecord->getFillable();
        
        $this->assertContains('type', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertContains('cost', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('created_by', $fillable);
        $this->assertContains('updated_by', $fillable);
    }

    public function test_has_uuid()
    {
        $financeRecord = new FinanceRecord();
        $financeRecord->id = Str::uuid(); // Explicitly set a UUID
        $financeRecord->type = 'expense';
        $financeRecord->category = 'utilities';
        $financeRecord->cost = 150.00;
        $financeRecord->description = 'Electricity bill';
        $financeRecord->created_by = $this->user->id;
        $financeRecord->updated_by = $this->user->id;
        $financeRecord->save();
        
        // Refresh from database to ensure we get what was actually stored
        $savedRecord = FinanceRecord::find($financeRecord->id);
        
        $this->assertNotNull($savedRecord->id);
        $this->assertIsString($savedRecord->id);
        // Test UUID format using regex pattern
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $savedRecord->id);
        
        // Clean up
        $financeRecord->delete();
    }

    public function test_has_timestamps()
    {
        $financeRecord = new FinanceRecord();
        $financeRecord->id = Str::uuid(); // Explicitly set a UUID
        $financeRecord->type = 'expense';
        $financeRecord->category = 'marketing';
        $financeRecord->cost = 750.00;
        $financeRecord->description = 'Advertising campaign';
        $financeRecord->created_by = $this->user->id;
        $financeRecord->updated_by = $this->user->id;
        $financeRecord->save();
        
        $this->assertNotNull($financeRecord->created_at);
        $this->assertNotNull($financeRecord->updated_at);
        
        // Test timestamps are instances of Carbon
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $financeRecord->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $financeRecord->updated_at);
        
        // Clean up
        $financeRecord->delete();
    }

    public function test_has_relationships()
    {
        $financeRecord = new FinanceRecord();
        $financeRecord->id = Str::uuid(); // Explicitly set a UUID
        $financeRecord->type = 'expense';
        $financeRecord->category = 'office';
        $financeRecord->cost = 300.00;
        $financeRecord->description = 'Office supplies';
        $financeRecord->created_by = $this->user->id;
        $financeRecord->updated_by = $this->user->id;
        $financeRecord->save();

        $this->assertInstanceOf(User::class, $financeRecord->createdBy);
        $this->assertEquals($this->user->name, $financeRecord->createdBy->name);
        
        $this->assertInstanceOf(User::class, $financeRecord->updatedBy);
        $this->assertEquals($this->user->name, $financeRecord->updatedBy->name);
        
        // Clean up
        $financeRecord->delete();
    }

    public function test_update_finance_record()
    {
        $financeRecord = new FinanceRecord();
        $financeRecord->id = Str::uuid(); // Explicitly set a UUID
        $financeRecord->type = 'expense';
        $financeRecord->category = 'repair';
        $financeRecord->cost = 250.00;
        $financeRecord->description = 'Initial repair description';
        $financeRecord->created_by = $this->user->id;
        $financeRecord->updated_by = $this->user->id;
        $financeRecord->save();

        // Update the finance record
        $financeRecord->cost = 350.00;
        $financeRecord->description = 'Updated repair description';
        $financeRecord->save();
        
        // Refresh from database
        $updatedRecord = FinanceRecord::find($financeRecord->id);
        
        $this->assertEquals(350.00, $updatedRecord->cost);
        $this->assertEquals('Updated repair description', $updatedRecord->description);
        
        // Clean up
        $financeRecord->delete();
    }

    public function test_find_finance_records_by_criteria()
    {
        // Create multiple finance records for testing
        $record1 = new FinanceRecord();
        $record1->id = Str::uuid();
        $record1->type = 'expense';
        $record1->category = 'utilities';
        $record1->cost = 150.00;
        $record1->description = 'Electricity bill';
        $record1->created_by = $this->user->id;
        $record1->updated_by = $this->user->id;
        $record1->save();
        
        $record2 = new FinanceRecord();
        $record2->id = Str::uuid();
        $record2->type = 'expense';
        $record2->category = 'maintenance';
        $record2->cost = 500.00;
        $record2->description = 'Showroom repair';
        $record2->created_by = $this->user->id;
        $record2->updated_by = $this->user->id;
        $record2->save();
        
        $record3 = new FinanceRecord();
        $record3->id = Str::uuid();
        $record3->type = 'income';
        $record3->category = 'other';
        $record3->cost = 1000.00;
        $record3->description = 'Miscellaneous income';
        $record3->created_by = $this->user->id;
        $record3->updated_by = $this->user->id;
        $record3->save();
        
        // Test finding by type
        $expenseRecords = FinanceRecord::where('type', 'expense')->get();
        $this->assertEquals(2, $expenseRecords->count());
        
        $incomeRecords = FinanceRecord::where('type', 'income')->get();
        $this->assertEquals(1, $incomeRecords->count());
        
        // Test finding by category
        $utilityRecords = FinanceRecord::where('category', 'utilities')->get();
        $this->assertEquals(1, $utilityRecords->count());
        
        // Test finding by cost range
        $highCostRecords = FinanceRecord::where('cost', '>=', 500)->get();
        $this->assertEquals(2, $highCostRecords->count());
        
        // Test finding with multiple criteria
        $expensiveExpenses = FinanceRecord::where('type', 'expense')
                                  ->where('cost', '>=', 500)
                                  ->get();
        $this->assertEquals(1, $expensiveExpenses->count());
        $this->assertEquals('maintenance', $expensiveExpenses->first()->category);
        
        // Clean up
        $record1->delete();
        $record2->delete();
        $record3->delete();
    }

    public function test_calculate_totals_by_type()
    {
        // Create multiple finance records for testing
        $record1 = new FinanceRecord();
        $record1->id = Str::uuid();
        $record1->type = 'expense';
        $record1->category = 'utilities';
        $record1->cost = 150.00;
        $record1->created_by = $this->user->id;
        $record1->updated_by = $this->user->id;
        $record1->save();
        
        $record2 = new FinanceRecord();
        $record2->id = Str::uuid();
        $record2->type = 'expense';
        $record2->category = 'maintenance';
        $record2->cost = 500.00;
        $record2->created_by = $this->user->id;
        $record2->updated_by = $this->user->id;
        $record2->save();
        
        $record3 = new FinanceRecord();
        $record3->id = Str::uuid();
        $record3->type = 'income';
        $record3->category = 'other';
        $record3->cost = 1000.00;
        $record3->created_by = $this->user->id;
        $record3->updated_by = $this->user->id;
        $record3->save();
        
        // Calculate total expenses
        $totalExpenses = FinanceRecord::where('type', 'expense')->sum('cost');
        $this->assertEquals(650.00, $totalExpenses);
        
        // Calculate total income
        $totalIncome = FinanceRecord::where('type', 'income')->sum('cost');
        $this->assertEquals(1000.00, $totalIncome);
        
        // Calculate net (income - expenses)
        $net = $totalIncome - $totalExpenses;
        $this->assertEquals(350.00, $net);
        
        // Clean up
        $record1->delete();
        $record2->delete();
        $record3->delete();
    }

    public function test_delete_finance_record()
    {
        $financeRecord = new FinanceRecord();
        $financeRecord->id = Str::uuid();
        $financeRecord->type = 'expense';
        $financeRecord->category = 'utilities';
        $financeRecord->cost = 150.00;
        $financeRecord->description = 'Electricity bill';
        $financeRecord->created_by = $this->user->id;
        $financeRecord->updated_by = $this->user->id;
        $financeRecord->save();
        
        $recordId = $financeRecord->id;
        
        // Delete the record
        $financeRecord->delete();
        
        // Verify record is deleted
        $deletedRecord = FinanceRecord::find($recordId);
        $this->assertNull($deletedRecord);
    }
}
