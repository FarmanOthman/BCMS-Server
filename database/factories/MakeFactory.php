<?php

namespace Database\Factories;

use App\Models\Make;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MakeFactory extends Factory
{
    protected $model = Make::class;

    public function definition()
    {
        return [
            'id' => Str::uuid(),
            'name' => $this->faker->companySuffix . ' Motors',
            // created_by and updated_by can be added here if needed, e.g., User::factory()
        ];
    }
}
