<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MealChoiceProcessor;
use App\Services\Interfaces\DataProviderInterface;
use App\Models\Employee;
use App\Models\MealChoice;
use App\Models\SlackNotification;
use App\Jobs\SendSlackNotificationJob;
use Illuminate\Support\Facades\Queue;

class MealChoiceProcessorTest extends TestCase
{
    private MealChoiceProcessor $processor;
    private DataProviderInterface $dataProvider;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        
        $this->dataProvider = \Mockery::mock(DataProviderInterface::class);
        $this->processor = new MealChoiceProcessor($this->dataProvider);
    }

    public function test_process_creates_new_employees_and_meal_choices(): void
    {
        $this->dataProvider->shouldReceive('getData')
            ->once()
            ->andReturn([
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'choice' => 'Pizza',
                    'date' => '2024-01-15',
                    'slack_id' => 'U12345',
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'choice' => 'Burger',
                    'date' => '2024-01-16',
                    'slack_id' => null,
                ],
            ]);

        $result = $this->processor->process('test.csv');

        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(2, $result['total']);

        $this->assertDatabaseHas('employees', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'slack_id' => 'U12345',
        ]);

        $this->assertDatabaseHas('employees', [
            'email' => 'jane@example.com',
            'name' => 'Jane Smith',
        ]);

        $john = Employee::where('email', 'john@example.com')->first();
        $this->assertDatabaseHas('meal_choices', [
            'employee_id' => $john->id,
            'choice' => 'Pizza',
        ]);
        
        $mealChoice = MealChoice::where('employee_id', $john->id)->first();
        $this->assertEquals('2024-01-15', $mealChoice->date->format('Y-m-d'));

        Queue::assertPushed(SendSlackNotificationJob::class, 2);
    }

    public function test_process_updates_existing_meal_choices(): void
    {
        $employee = Employee::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        MealChoice::factory()->create([
            'employee_id' => $employee->id,
            'choice' => 'Old Choice',
            'date' => '2024-01-15',
        ]);

        $this->dataProvider->shouldReceive('getData')
            ->once()
            ->andReturn([
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'choice' => 'New Choice',
                    'date' => '2024-01-15',
                    'slack_id' => null,
                ],
            ]);

        $result = $this->processor->process('test.csv');

        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, $result['total']);

        $this->assertDatabaseHas('meal_choices', [
            'employee_id' => $employee->id,
            'choice' => 'New Choice',
        ]);
        
        $mealChoice = MealChoice::where('employee_id', $employee->id)->first();
        $this->assertEquals('2024-01-15', $mealChoice->date->format('Y-m-d'));
    }

    public function test_process_updates_employee_data_when_changed(): void
    {
        $employee = Employee::factory()->create([
            'email' => 'john@example.com',
            'name' => 'Old Name',
            'slack_id' => null,
        ]);

        $this->dataProvider->shouldReceive('getData')
            ->once()
            ->andReturn([
                [
                    'name' => 'New Name',
                    'email' => 'john@example.com',
                    'choice' => 'Pizza',
                    'date' => '2024-01-15',
                    'slack_id' => 'U12345',
                ],
            ]);

        $this->processor->process('test.csv');

        $employee->refresh();
        $this->assertEquals('New Name', $employee->name);
        $this->assertEquals('U12345', $employee->slack_id);
    }

    public function test_process_creates_slack_notification_records(): void
    {
        $this->dataProvider->shouldReceive('getData')
            ->once()
            ->andReturn([
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'choice' => 'Pizza',
                    'date' => '2024-01-15',
                    'slack_id' => null,
                ],
            ]);

        $this->processor->process('test.csv');

        $employee = Employee::where('email', 'john@example.com')->first();
        $mealChoice = MealChoice::where('employee_id', $employee->id)->first();

        $this->assertDatabaseHas('slack_notifications', [
            'meal_choice_id' => $mealChoice->id,
            'status' => 'pending',
        ]);
    }

    public function test_process_throws_exception_when_no_data(): void
    {
        $this->dataProvider->shouldReceive('getData')
            ->once()
            ->andReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No valid data found in file');

        $this->processor->process('test.csv');
    }

    public function test_process_handles_mixed_new_and_updated(): void
    {
        $existingEmployee = Employee::factory()->create([
            'email' => 'existing@example.com',
        ]);

        MealChoice::factory()->create([
            'employee_id' => $existingEmployee->id,
            'date' => '2024-01-15',
        ]);

        $this->dataProvider->shouldReceive('getData')
            ->once()
            ->andReturn([
                [
                    'name' => 'New Employee',
                    'email' => 'new@example.com',
                    'choice' => 'Pizza',
                    'date' => '2024-01-16',
                    'slack_id' => null,
                ],
                [
                    'name' => $existingEmployee->name,
                    'email' => 'existing@example.com',
                    'choice' => 'Updated Choice',
                    'date' => '2024-01-15',
                    'slack_id' => null,
                ],
            ]);

        $result = $this->processor->process('test.csv');

        $this->assertEquals(1, $result['created']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(2, $result['total']);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}

