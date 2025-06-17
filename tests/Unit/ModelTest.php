<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Make;
use App\Models\Model;
use App\Models\Car;
use Tests\TestCase;

class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_create_a_model()
    {
        $make = new Make();
        $make->name = 'Toyota';
        $make->save();
        
        $model = new Model();
        $model->name = 'Corolla';
        $model->make_id = $make->id;
        $model->save();

        $this->assertInstanceOf(Model::class, $model);
        $this->assertEquals('Corolla', $model->name);
        $this->assertEquals($make->id, $model->make_id);
        
        // Cleanup
        $model->delete();
        $make->delete();
    }

    public function test_has_fillable_fields()
    {
        $fillable = (new Model())->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('make_id', $fillable);
    }

    public function test_uses_uuid_as_primary_key()
    {
        $make = new Make();
        $make->name = 'BMW';
        $make->save();
        
        $model = new Model();
        $model->name = '3 Series';
        $model->make_id = $make->id;
        $model->save();
        
        $this->assertIsString($model->id);
        $this->assertEquals(36, strlen($model->id));
        
        // Cleanup
        $model->delete();
        $make->delete();
    }

    public function test_has_timestamps()
    {
        $make = new Make();
        $make->name = 'Mercedes';
        $make->save();
        
        $model = new Model();
        $model->name = 'C-Class';
        $model->make_id = $make->id;
        $model->save();
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $model->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $model->updated_at);
        
        // Cleanup
        $model->delete();
        $make->delete();
    }

    public function test_belongs_to_make()
    {
        $make = new Make();
        $make->name = 'Audi';
        $make->save();
        
        $model = new Model();
        $model->name = 'A4';
        $model->make_id = $make->id;
        $model->save();
        
        $this->assertInstanceOf(Make::class, $model->make);
        $this->assertEquals('Audi', $model->make->name);
        
        // Cleanup
        $model->delete();
        $make->delete();
    }

    public function test_can_have_multiple_cars()
    {
        $make = new Make();
        $make->name = 'Honda';
        $make->save();
        
        $model = new Model();
        $model->name = 'Civic';
        $model->make_id = $make->id;
        $model->save();
        
        for ($i = 0; $i < 3; $i++) {
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
        
        $this->assertCount(3, $model->cars);
        $this->assertInstanceOf(Car::class, $model->cars->first());
        
        // Cleanup
        foreach ($model->cars as $car) {
            $car->delete();
        }
        $model->delete();
        $make->delete();
    }

    public function test_can_find_models_by_make()
    {
        $make = new Make();
        $make->name = 'Ford';
        $make->save();
        
        $model1 = new Model();
        $model1->name = 'Focus';
        $model1->make_id = $make->id;
        $model1->save();
        
        $model2 = new Model();
        $model2->name = 'Mustang';
        $model2->make_id = $make->id;
        $model2->save();
        
        $foundModels = Model::where('make_id', $make->id)->get();
        
        $this->assertCount(2, $foundModels);
        $this->assertTrue($foundModels->contains('name', 'Focus'));
        $this->assertTrue($foundModels->contains('name', 'Mustang'));
        
        // Cleanup
        $model1->delete();
        $model2->delete();
        $make->delete();
    }

    public function test_can_update_a_model()
    {
        $make = new Make();
        $make->name = 'Toyota';
        $make->save();
        
        $model = new Model();
        $model->name = 'Camry';
        $model->make_id = $make->id;
        $model->save();
        
        $modelId = $model->id;
        
        $model->update([
            'name' => 'Camry SE'
        ]);
        
        $updatedModel = Model::find($modelId);
        
        $this->assertEquals('Camry SE', $updatedModel->name);
        
        // Cleanup
        $model->delete();
        $make->delete();
    }

    public function test_can_delete_a_model()
    {
        $make = new Make();
        $make->name = 'Nissan';
        $make->save();
        
        $model = new Model();
        $model->name = 'Altima';
        $model->make_id = $make->id;
        $model->save();
        
        $modelId = $model->id;
        
        $model->delete();
        
        $this->assertNull(Model::find($modelId));
        
        // Cleanup
        $make->delete();
    }

    public function test_cascade_delete_doesnt_remove_make()
    {
        $make = new Make();
        $make->name = 'Lexus';
        $make->save();
        
        $model = new Model();
        $model->name = 'ES';
        $model->make_id = $make->id;
        $model->save();
        
        $makeId = $make->id;
        $modelId = $model->id;
        
        $model->delete();
        
        $this->assertNull(Model::find($modelId));
        $this->assertNotNull(Make::find($makeId));
        
        // Cleanup
        $make->delete();
    }
}
