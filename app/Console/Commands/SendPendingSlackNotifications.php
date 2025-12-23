<?php

namespace App\Console\Commands;

use App\Models\MealChoice;
use App\Models\Employee;
use App\Jobs\SendSlackNotificationJob;
use Illuminate\Console\Command;

class SendPendingSlackNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:send-pending 
                            {--limit=50 : Maximum number of notifications to send}
                            {--force : Send even if already sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Slack notifications for pending meal choices';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = MealChoice::with(['employee', 'slackNotification']);

        if (!$force) {
            $query->where(function ($q) {
                $q->whereDoesntHave('slackNotification')
                  ->orWhereHas('slackNotification', function ($subQ) {
                      $subQ->whereIn('status', ['pending', 'failed']);
                  });
            });
        }

        $mealChoices = $query->limit($limit)->get();

        if ($mealChoices->isEmpty()) {
            $totalMealChoices = MealChoice::count();
            $totalWithSentStatus = MealChoice::whereHas('slackNotification', function ($q) {
                $q->where('status', 'sent');
            })->count();
            $totalPending = MealChoice::whereHas('slackNotification', function ($q) {
                $q->where('status', 'pending');
            })->count();
            
            $this->info('No pending Slack notifications found.');
            $this->newLine();
            $this->line('=== Database Statistics ===');
            $this->line("Total meal choices: {$totalMealChoices}");
            $this->line("Meal choices with 'sent' status: {$totalWithSentStatus}");
            $this->line("Meal choices with 'pending' status: {$totalPending}");
            
            return Command::SUCCESS;
        }

        $this->info("Found {$mealChoices->count()} meal choice(s) to notify.");

        $bar = $this->output->createProgressBar($mealChoices->count());
        $bar->start();

        foreach ($mealChoices as $mealChoice) {
            $wasNew = !$mealChoice->slackNotification || 
                      $mealChoice->slackNotification->status !== 'sent';
            
            SendSlackNotificationJob::dispatch($mealChoice, $mealChoice->employee, $wasNew);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Dispatched {$mealChoices->count()} notification job(s).");

        return Command::SUCCESS;
    }
}

