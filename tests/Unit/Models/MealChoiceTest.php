<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Employee;
use App\Models\MealChoice;
use App\Models\SlackNotification;

class MealChoiceTest extends TestCase
{
    public function test_meal_choice_belongs_to_employee(): void
    {
        $employee = Employee::factory()->create();
        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->assertInstanceOf(Employee::class, $mealChoice->employee);
        $this->assertEquals($employee->id, $mealChoice->employee->id);
    }

    public function test_meal_choice_has_one_slack_notification(): void
    {
        $employee = Employee::factory()->create();
        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $notification = SlackNotification::factory()->create([
            'meal_choice_id' => $mealChoice->id,
        ]);

        $this->assertInstanceOf(SlackNotification::class, $mealChoice->slackNotification);
        $this->assertEquals($notification->id, $mealChoice->slackNotification->id);
    }

    public function test_to_api_array_returns_correct_format(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'slack_id' => 'U12345',
        ]);

        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
            'choice' => 'Pizza',
            'date' => '2024-01-15',
        ]);

        $apiArray = $mealChoice->toApiArray();

        $this->assertEquals($mealChoice->id, $apiArray['id']);
        $this->assertEquals('Pizza', $apiArray['choice']);
        $this->assertEquals('2024-01-15', $apiArray['date']);
        $this->assertEquals('John Doe', $apiArray['employee']['name']);
        $this->assertEquals('john@example.com', $apiArray['employee']['email']);
        $this->assertEquals('U12345', $apiArray['employee']['slack_id']);
        $this->assertEquals('pending', $apiArray['slack_status']);
    }

    public function test_to_api_array_includes_slack_notification_status(): void
    {
        $employee = Employee::factory()->create();
        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
        ]);

        SlackNotification::factory()->create([
            'meal_choice_id' => $mealChoice->id,
            'status' => 'sent',
        ]);

        $apiArray = $mealChoice->toApiArray();

        $this->assertEquals('sent', $apiArray['slack_status']);
    }

    public function test_get_statistics_returns_correct_counts(): void
    {
        $employeeWithSlack = Employee::factory()->create(['slack_id' => 'U12345']);
        $employeeWithoutSlack = Employee::factory()->create(['slack_id' => null]);

        MealChoice::factory()->count(2)->create([
            'employee_id' => $employeeWithSlack->id,
        ]);

        MealChoice::factory()->count(3)->create([
            'employee_id' => $employeeWithoutSlack->id,
        ]);

        $stats = MealChoice::getStatistics();

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(2, $stats['with_slack_id']);
        $this->assertEquals(3, $stats['without_slack_id']);
    }

    public function test_get_formatted_for_api_returns_collection(): void
    {
        $employee = Employee::factory()->create();
        MealChoice::factory()->count(3)->create([
            'employee_id' => $employee->id,
        ]);

        $result = MealChoice::getFormattedForApi();

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('id', $result->first());
        $this->assertArrayHasKey('employee', $result->first());
    }

    public function test_get_formatted_for_api_orders_by_date_desc(): void
    {
        $employee = Employee::factory()->create();
        
        MealChoice::factory()->create([
            'employee_id' => $employee->id,
            'date' => '2024-01-15',
        ]);
        
        MealChoice::factory()->create([
            'employee_id' => $employee->id,
            'date' => '2024-01-17',
        ]);
        
        MealChoice::factory()->create([
            'employee_id' => $employee->id,
            'date' => '2024-01-16',
        ]);

        $result = MealChoice::getFormattedForApi();

        $this->assertEquals('2024-01-17', $result->first()['date']);
        $this->assertEquals('2024-01-15', $result->last()['date']);
    }
}

