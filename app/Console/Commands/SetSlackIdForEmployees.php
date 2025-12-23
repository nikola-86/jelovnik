<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

class SetSlackIdForEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:set-slack-id 
                            {slack_id : The Slack ID to set}
                            {--all : Set for all employees}
                            {--email= : Set for specific employee email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Slack ID for employees';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slackId = $this->argument('slack_id');
        $all = $this->option('all');
        $email = $this->option('email');

        if (!$all && !$email) {
            $this->error('You must specify either --all or --email=example@email.com');
            return Command::FAILURE;
        }

        if ($all) {
            $employees = Employee::all();
            $this->info("Setting Slack ID '{$slackId}' for all {$employees->count()} employees...");
        } else {
            $employee = Employee::where('email', $email)->first();
            if (!$employee) {
                $this->error("Employee with email '{$email}' not found.");
                return Command::FAILURE;
            }
            $employees = collect([$employee]);
            /** @var Employee $employee */
            $this->info("Setting Slack ID '{$slackId}' for employee: {$employee->name} ({$employee->email})");
        }

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        $updated = 0;
        foreach ($employees as $employee) {
            /** @var Employee $employee */
            $employee->slack_id = $slackId;
            $employee->save();
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated {$updated} employee(s) with Slack ID '{$slackId}'");

        return Command::SUCCESS;
    }
}

