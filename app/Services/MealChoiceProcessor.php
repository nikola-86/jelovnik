<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\MealChoice;
use App\Models\SlackNotification;
use App\Services\Interfaces\DataProviderInterface;
use App\Jobs\SendSlackNotificationJob;
use Illuminate\Support\Facades\DB;

class MealChoiceProcessor
{
    public function __construct(
        private DataProviderInterface $dataProvider
    ) {}

    /**
     * Process meal choices from a source
     *
     * @param string $source Source identifier (file path, date, etc.)
     * @return array Statistics: ['created' => int, 'updated' => int, 'total' => int]
     * @throws \RuntimeException
     */
    public function process(string $source): array
    {
        $rows = $this->dataProvider->getData($source);

        if (empty($rows)) {
            throw new \RuntimeException('No valid data found in file');
        }

        return DB::transaction(function () use ($rows) {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $employee = $this->findOrCreateEmployee($row);
                [$mealChoice, $wasNew] = $this->createOrUpdateMealChoice($employee, $row);
                
                if ($wasNew) {
                    $created++;
                } else {
                    $updated++;
                }

                $this->queueNotification($mealChoice, $employee, $wasNew);
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'total' => count($rows),
            ];
        });
    }

    /**
     * Find or create an employee and update if needed
     */
    private function findOrCreateEmployee(array $row): Employee
    {
        /** @var Employee $employee */
        $employee = Employee::firstOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'slack_id' => $row['slack_id'] ?? null,
            ]
        );

        $this->updateEmployeeIfNeeded($employee, $row);

        return $employee;
    }

    /**
     * Update employee data if it has changed
     */
    private function updateEmployeeIfNeeded(Employee $employee, array $row): void
    {
        $updateData = [];

        if ($employee->name !== $row['name']) {
            $updateData['name'] = $row['name'];
        }

        if (!empty($row['slack_id']) && $employee->slack_id !== $row['slack_id']) {
            $updateData['slack_id'] = $row['slack_id'];
        }

        if (!empty($updateData)) {
            $employee->update($updateData);
        }
    }

    /**
     * Create or update a meal choice
     *
     * @return array [MealChoice $mealChoice, bool $wasNew]
     */
    private function createOrUpdateMealChoice(Employee $employee, array $row): array
    {
        $date = \Carbon\Carbon::parse($row['date']);
        
        /** @var MealChoice $mealChoice */
        $mealChoice = MealChoice::where('employee_id', $employee->id)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();
        
        $wasNew = $mealChoice === null;
        
        if ($wasNew) {
            $mealChoice = new MealChoice();
            $mealChoice->employee_id = $employee->id;
            $mealChoice->date = $date->format('Y-m-d');
        }

        $mealChoice->choice = $row['choice'];
        $mealChoice->save();

        return [$mealChoice, $wasNew];
    }

    /**
     * Queue a notification for the meal choice
     */
    private function queueNotification(MealChoice $mealChoice, Employee $employee, bool $isNew): void
    {
        SlackNotification::firstOrCreate(
            ['meal_choice_id' => $mealChoice->id],
            ['status' => 'pending', 'sent_at' => null]
        );

        SendSlackNotificationJob::dispatch($mealChoice, $employee, $isNew);
    }
}

