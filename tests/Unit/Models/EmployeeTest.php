<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Employee;
use App\Models\MealChoice;

class EmployeeTest extends TestCase
{
    public function test_employee_has_many_meal_choices(): void
    {
        $employee = Employee::factory()->create();
        
        MealChoice::factory()->count(3)->create([
            'employee_id' => $employee->id,
        ]);

        $this->assertCount(3, $employee->mealChoices);
        $this->assertInstanceOf(MealChoice::class, $employee->mealChoices->first());
    }

    public function test_get_statistics_returns_correct_counts(): void
    {
        Employee::factory()->create(['slack_id' => 'U12345']);
        Employee::factory()->create(['slack_id' => 'U67890']);
        Employee::factory()->create(['slack_id' => null]);
        Employee::factory()->create(['slack_id' => '']);

        $stats = Employee::getStatistics();

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['with_slack_id']);
        $this->assertEquals(2, $stats['without_slack_id']);
    }

    public function test_get_statistics_handles_empty_database(): void
    {
        $stats = Employee::getStatistics();

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['with_slack_id']);
        $this->assertEquals(0, $stats['without_slack_id']);
    }
}

