<?php

namespace App\Services\Implementations;

use App\Models\Employee;
use App\Models\MealChoice;
use App\Services\Interfaces\NotifierInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotifier implements NotifierInterface
{
    private ?string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.slack.webhook_url');
    }

    public function notify(Employee $employee, MealChoice $mealChoice, bool $isNew): bool
    {
        $this->validateConfiguration();

        try {
            $message = $this->buildMessage($employee, $mealChoice, $isNew);
            $recipient = $this->determineRecipient($employee);
            $formattedMessage = $this->formatMessage($recipient, $message);
            $payload = $this->buildPayload($formattedMessage);
            $response = $this->sendRequest($payload);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    /**
     * Send a simple test message (for testing Slack connection)
     */
    public function sendTestMessage(string $channel, string $message): bool
    {
        $this->validateConfiguration();

        try {
            $payload = $this->buildPayload($message);
            $response = $this->sendRequest($payload);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    /**
     * Build notification message from domain objects
     */
    private function buildMessage(Employee $employee, MealChoice $mealChoice, bool $isNew): string
    {
        $action = $isNew ? 'New meal choice recorded!' : 'Meal choice updated!';
        
        return "{$action}\n*Employee:* {$employee->name}\n*Meal:* {$mealChoice->choice}\n*Date:* {$mealChoice->date->format('Y-m-d')}";
    }

    /**
     * Determine recipient (Slack ID or default channel)
     */
    private function determineRecipient(Employee $employee): string
    {
        $slackId = $employee->slack_id;
        
        if (empty($slackId)) {
            return '#general';
        }

        if (str_starts_with($slackId, '@') || str_starts_with($slackId, '#')) {
            return $slackId;
        }

        return '@' . $slackId;
    }

    /**
     * Format message with user mention if recipient is a user
     */
    private function formatMessage(string $recipient, string $message): string
    {
        if (str_starts_with($recipient, '#')) {
            return $message;
        }

        $userId = str_starts_with($recipient, '@') ? substr($recipient, 1) : $recipient;
        return "<@{$userId}> " . $message;
    }

    /**
     * Validate webhook URL is configured
     */
    private function validateConfiguration(): void
    {
        if (empty($this->webhookUrl)) {
            Log::error('Slack webhook URL not configured in .env (SLACK_WEBHOOK_URL)');
            throw new \RuntimeException('Slack webhook URL is not configured. Please set SLACK_WEBHOOK_URL in your .env file.');
        }
    }

    /**
     * Build Slack webhook payload
     */
    private function buildPayload(string $message): array
    {
        return [
            'text' => $message,
        ];
    }

    /**
     * Send HTTP request to Slack webhook
     */
    private function sendRequest(array $payload): \Illuminate\Http\Client\Response
    {
        return Http::timeout(10)->post($this->webhookUrl, $payload);
    }

    /**
     * Handle HTTP response and return success status
     */
    private function handleResponse(\Illuminate\Http\Client\Response $response): bool
    {
        if ($response->successful()) {
            return true;
        }

        $this->logError('Slack webhook failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    /**
     * Log error message
     */
    private function logError(string $message, array $context = []): void
    {
        if (empty($context)) {
            Log::error('Slack webhook error: ' . $message);
        } else {
            Log::error($message, $context);
        }
    }
}

