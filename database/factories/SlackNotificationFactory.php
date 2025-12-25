<?php

namespace Database\Factories;

use App\Models\SlackNotification;
use App\Models\MealChoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlackNotification>
 */
class SlackNotificationFactory extends Factory
{
    protected $model = SlackNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_choice_id' => MealChoice::factory(),
            'status' => fake()->randomElement(['pending', 'sent', 'failed']),
            'sent_at' => fake()->optional()->dateTime(),
        ];
    }
}

