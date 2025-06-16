<?php

namespace Database\Factories;

use App\Models\Model as CarModel;
use App\Models\Make;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ModelFactory extends Factory
{
    protected $model = CarModel::class;

    public function definition()
    {
        return [
            'id' => Str::uuid(),
            'make_id' => Make::factory(),
            'name' => $this->faker->word . ' ' . $this->faker->word,
            // created_by and updated_by can be added here if needed
        ];
    }
}
