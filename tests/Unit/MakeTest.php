<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Make;
use App\Models\Model;
use App\Models\Car;
use Tests\TestCase;

class MakeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_a_make()
    {
        $make = new Make();
        $make->name = 'Toyota';
        $make->save();

        $this->assertInstanceOf(Make::class, $make);
        $this->assertEquals('Toyota', $make->name);
        
        // Cleanup
        $make->delete();
    }

    public function test_has_fillable_fields()
    {
        $fillable = (new Make())->getFillable();

        $this->assertContains('name', $fillable);
    }

    public function test_uses_uuid_as_primary_key()
    {
        $make = new Make();
        $make->name = 'BMW';
        $make->save();
        
        $this->assertIsString($make->id);
        $this->assertEquals(36, strlen($make->id));
        
        // Cleanup
        $make->delete();
    }

    public function test_has_timestamps()
    {
        $make = new Make();
        $make->name = 'Mercedes';
        $make->save();
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $make->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $make->updated_at);
        
        // Cleanup
        $make->delete();
    }    public function test_can_have_multiple_models()
    {
        $make = new Make();
        $make->name = 'Ford';
        $make->save();
        
        for ($i = 0; $i < 3; $i++) {
            $model = new Model();
            $model->name = "Ford Model " . ($i + 1);
            $model->make_id = $make->id;
            $model->save();
        }
        
        $this->assertCount(3, $make->models);
        $this->assertInstanceOf(Model::class, $make->models->first());
        
        // Cleanup
        foreach ($make->models as $model) {
            $model->delete();
        }
        $make->delete();
    }    public function test_can_have_multiple_cars()
    {
        $make = new Make();
        $make->name = 'Honda';
        $make->save();
        
        $model = new Model();
        $model->name = 'Civic';
        $model->make_id = $make->id;
        $model->save();
        
        for ($i = 0; $i < 5; $i++) {
            $car = new Car();
            $car->vin = 'VIN' . rand(10000, 99999);
            $car->make_id = $make->id;
            $car->model_id = $model->id;
            $car->year = 2025;
            $car->cost_price = 20000;
            $car->selling_price = 25000;
            $car->public_price = 26000;
            $car->transition_cost = 500;
            $car->total_repair_cost = 0;
            $car->status = 'available';
            $car->save();
        }
        
        $this->assertCount(5, $make->cars);
        $this->assertInstanceOf(Car::class, $make->cars->first());
        
        // Cleanup
        foreach ($make->cars as $car) {
            $car->delete();
        }
        $model->delete();
        $make->delete();
    }

    public function test_can_find_a_make_by_name()
    {
        $make1 = new Make();
        $make1->name = 'Honda';
        $make1->save();
        
        $make2 = new Make();
        $make2->name = 'Toyota';
        $make2->save();
        
        $foundMake = Make::where('name', 'Toyota')->first();
        
        $this->assertInstanceOf(Make::class, $foundMake);
        $this->assertEquals('Toyota', $foundMake->name);
        
        // Cleanup
        $make1->delete();
        $make2->delete();
    }

    public function test_can_update_a_make()
    {
        $make = new Make();
        $make->name = 'Toyota';
        $make->save();
        
        $makeId = $make->id;
        
        $make->update([
            'name' => 'Toyota Motors'
        ]);
        
        $updatedMake = Make::find($makeId);
        
        $this->assertEquals('Toyota Motors', $updatedMake->name);
        
        // Cleanup
        $make->delete();
    }

    public function test_can_delete_a_make()
    {
        $make = new Make();
        $make->name = 'Nissan';
        $make->save();
        
        $makeId = $make->id;
        
        $make->delete();
        
        $this->assertNull(Make::find($makeId));
    }
}
