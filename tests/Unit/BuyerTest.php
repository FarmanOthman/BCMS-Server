<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Buyer;
use App\Models\User;
use Tests\TestCase;

class BuyerTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for all tests
        $this->user = new User();
        $this->user->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
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

    public function test_can_create_buyer()
    {
        $buyer = new Buyer();
        $buyer->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $buyer->name = 'Test Buyer';
        $buyer->phone = 'TEST' . rand(10000, 99999);
        $buyer->address = '123 Test St';
        $buyer->created_by = $this->user->id;
        $buyer->updated_by = $this->user->id;
        $buyer->save();

        $this->assertInstanceOf(Buyer::class, $buyer);
        $this->assertEquals('Test Buyer', $buyer->name);
        $this->assertTrue(str_starts_with($buyer->phone, 'TEST'));
        $this->assertEquals('123 Test St', $buyer->address);
        
        // Clean up
        $buyer->delete();
    }

    public function test_fillable_attributes()
    {
        $buyer = new Buyer();
        
        $fillable = $buyer->getFillable();
        
        $this->assertContains('id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('address', $fillable);
        $this->assertContains('created_by', $fillable);
        $this->assertContains('updated_by', $fillable);
    }    public function test_has_uuid()
    {
        $buyer = new Buyer();
        // Explicitly set UUID to ensure it works with the database
        $buyer->id = \Illuminate\Support\Str::uuid();
        $buyer->name = 'UUID Test Buyer';
        $buyer->phone = 'TEST' . rand(10000, 99999);
        $buyer->created_by = $this->user->id;
        $buyer->updated_by = $this->user->id;
        $buyer->save();
        
        // Refresh from database to ensure we get what was actually stored
        $savedBuyer = Buyer::find($buyer->id);
        
        $this->assertNotNull($savedBuyer->id);
        $this->assertIsString($savedBuyer->id);
        // Test UUID format using regex pattern
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $savedBuyer->id);
        
        // Clean up
        $buyer->delete();
    }

    public function test_has_timestamps()
    {
        $buyer = new Buyer();
        $buyer->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $buyer->name = 'Timestamp Test Buyer';
        $buyer->phone = 'TEST' . rand(10000, 99999);
        $buyer->created_by = $this->user->id;
        $buyer->updated_by = $this->user->id;
        $buyer->save();
        
        $this->assertNotNull($buyer->created_at);
        $this->assertNotNull($buyer->updated_at);
        
        // Test timestamps are instances of Carbon
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $buyer->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $buyer->updated_at);
        
        // Clean up
        $buyer->delete();
    }

    public function test_has_relationships()
    {
        $buyer = new Buyer();
        $buyer->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $buyer->name = 'Relationship Test Buyer';
        $buyer->phone = 'TEST' . rand(10000, 99999);
        $buyer->created_by = $this->user->id;
        $buyer->updated_by = $this->user->id;
        $buyer->save();

        $this->assertInstanceOf(User::class, $buyer->createdBy);
        $this->assertEquals($this->user->name, $buyer->createdBy->name);
        
        $this->assertInstanceOf(User::class, $buyer->updatedBy);
        $this->assertEquals($this->user->name, $buyer->updatedBy->name);
        
        // Clean up
        $buyer->delete();
    }

    public function test_update_buyer()
    {
        $buyer = new Buyer();
        $buyer->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $buyer->name = 'Original Name';
        $buyer->phone = 'TEST' . rand(10000, 99999);
        $buyer->address = 'Original Address';
        $buyer->created_by = $this->user->id;
        $buyer->updated_by = $this->user->id;
        $buyer->save();

        // Update the buyer
        $buyer->name = 'Updated Name';
        $buyer->address = 'Updated Address';
        $buyer->save();
        
        // Refresh from database
        $buyer = Buyer::find($buyer->id);
        
        $this->assertEquals('Updated Name', $buyer->name);
        $this->assertEquals('Updated Address', $buyer->address);
        
        // Clean up
        $buyer->delete();
    }    public function test_find_buyers_by_criteria()
    {
        // Create multiple buyers for testing
        $buyer1 = new Buyer();
        $buyer1->id = \Illuminate\Support\Str::uuid();
        $buyer1->name = 'Buyer One';
        $buyer1->phone = 'TEST1' . rand(1000, 9999);
        $buyer1->address = 'Address One';
        $buyer1->created_by = $this->user->id;
        $buyer1->updated_by = $this->user->id;
        $buyer1->save();
        
        $buyer2 = new Buyer();
        $buyer2->id = \Illuminate\Support\Str::uuid();
        $buyer2->name = 'Buyer Two';
        $buyer2->phone = 'TEST2' . rand(1000, 9999);
        $buyer2->address = 'Address Two';
        $buyer2->created_by = $this->user->id;
        $buyer2->updated_by = $this->user->id;
        $buyer2->save();
        
        $buyer3 = new Buyer();
        $buyer3->id = \Illuminate\Support\Str::uuid();
        $buyer3->name = 'Different Name';
        $buyer3->phone = 'TEST3' . rand(1000, 9999);
        $buyer3->address = 'Different Address';
        $buyer3->created_by = $this->user->id;
        $buyer3->updated_by = $this->user->id;
        $buyer3->save();
        
        // For PostgreSQL compatibility, use ILIKE for case-insensitive searches
        // Test finding by name containing "Buyer"
        $buyersByName = Buyer::whereRaw("name ILIKE ?", ['%Buyer%'])->get();
        $this->assertEquals(2, $buyersByName->count());
        
        // Test finding by specific name (exact match)
        $specificBuyer = Buyer::where('name', 'Different Name')->first();
        $this->assertNotNull($specificBuyer);
        $this->assertEquals('Different Name', $specificBuyer->name);
        
        // Test finding by phone pattern using whereRaw for PostgreSQL compatibility
        $buyersByPhone = Buyer::whereRaw("phone LIKE ?", ['TEST1%'])->get();
        $this->assertEquals(1, $buyersByPhone->count());
        
        // Clean up
        $buyer1->delete();
        $buyer2->delete();
        $buyer3->delete();
    }

    public function test_delete_buyer()
    {
        $buyer = new Buyer();
        $buyer->id = \Illuminate\Support\Str::uuid();
        $buyer->name = 'Delete Test Buyer';
        $buyer->phone = 'TEST' . rand(10000, 99999);
        $buyer->address = 'Delete Test Address';
        $buyer->created_by = $this->user->id;
        $buyer->updated_by = $this->user->id;
        $buyer->save();
        
        $buyerId = $buyer->id;
        
        // Delete the buyer
        $buyer->delete();
        
        // Verify buyer is deleted
        $deletedBuyer = Buyer::find($buyerId);
        $this->assertNull($deletedBuyer);
    }
}
