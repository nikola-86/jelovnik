<?php

namespace App\Jobs;

use App\Models\MealChoice;
use App\Models\Employee;
use App\Models\SlackNotification;
use App\Services\Interfaces\NotifierInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSlackNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MealChoice $mealChoice,
        public Employee $employee,
        public bool $isNew
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotifierInterface $notifier): void
    {
        try {
            $success = $notifier->notify($this->employee, $this->mealChoice, $this->isNew);

            SlackNotification::updateOrCreate(
                ['meal_choice_id' => $this->mealChoice->id],
                [
                    'status' => $success ? 'sent' : 'failed',
                    'sent_at' => $success ? now() : null,
                ]
            );
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Failed to send Slack notification', [
                'meal_choice_id' => $this->mealChoice->id,
                'employee_id' => $this->employee->id,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);
            
            SlackNotification::updateOrCreate(
                ['meal_choice_id' => $this->mealChoice->id],
                [
                    'status' => 'failed',
                    'sent_at' => null,
                ]
            );
            
            if (str_contains($errorMessage, 'not configured')) {
                throw $e;
            }
        }
    }
}

