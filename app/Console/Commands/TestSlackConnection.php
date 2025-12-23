<?php

namespace App\Console\Commands;

use App\Services\Implementations\SlackNotifier;
use Illuminate\Console\Command;

class TestSlackConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Slack webhook connection';

    /**
     * Execute the console command.
     */
    public function handle(SlackNotifier $slackNotifier): int
    {
        $this->info('Testing Slack webhook connection...');
        $this->newLine();

        $webhookUrl = config('services.slack.webhook_url');
        
        if (empty($webhookUrl)) {
            $this->error('❌ SLACK_WEBHOOK_URL is not configured in .env file!');
            $this->line('Please add this line to your .env file:');
            $this->line('SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...');
            return Command::FAILURE;
        }

        $this->info('✓ Webhook URL is configured');
        $this->line('URL: ' . substr($webhookUrl, 0, 50) . '...');
        $this->newLine();

        $this->info('Sending test message to #general...');
        
        try {
            $success = $slackNotifier->sendTestMessage('#general', '🧪 Test message from Jelovnik application');
            
            if ($success) {
                $this->info('✅ Success! Check your Slack channel for the test message.');
                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to send message. Check logs for details:');
                $this->line('docker exec jelovnik-php tail -n 50 storage/logs/laravel.log');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            $this->line('Full error: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

