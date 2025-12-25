<?php

namespace Database\Factories;

use App\Models\MealChoice;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealChoice>
 */
class MealChoiceFactory extends Factory
{
    protected $model = MealChoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'choice' => fake()->randomElement(['Pizza', 'Burger', 'Salad', 'Pasta', 'Steak', 'Soup']),
            'date' => fake()->date(),
        ];
    }
}

