<?php

namespace Database\Factories;

use App\Models\FinanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FinanceRecordFactory extends Factory
{
    protected $model = FinanceRecord::class;

    public function definition()
    {
        $types = ['income', 'expense'];
        $categories = [
            'income' => ['sale', 'investment', 'refund', 'other'],
            'expense' => ['purchase', 'repair', 'transport', 'utilities', 'rent', 'salaries', 'other']
        ];
        
        $type = $this->faker->randomElement($types);
        
        return [
            'id' => Str::uuid(),
            'type' => $type,
            'category' => $this->faker->randomElement($categories[$type]),
            'cost' => $this->faker->randomFloat(2, 100, 5000),
            'record_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'description' => $this->faker->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
    
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function income()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'income',
                'category' => $this->faker->randomElement(['sale', 'investment', 'refund', 'other']),
            ];
        });
    }
    
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function expense()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'expense',
                'category' => $this->faker->randomElement(['purchase', 'repair', 'transport', 'utilities', 'rent', 'salaries', 'other']),
            ];
        });
    }
}
