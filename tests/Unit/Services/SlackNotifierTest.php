<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Implementations\SlackNotifier;
use App\Models\Employee;
use App\Models\MealChoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SlackNotifierTest extends TestCase
{
    private SlackNotifier $notifier;
    private string $webhookUrl = 'https://hooks.slack.com/services/test/webhook/url';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.slack.webhook_url', $this->webhookUrl);
        $this->notifier = new SlackNotifier();
    }

    public function test_notify_sends_message_for_new_meal_choice(): void
    {
        Http::fake([
            $this->webhookUrl => Http::response(['ok' => true], 200),
        ]);

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

        $result = $this->notifier->notify($employee, $mealChoice, true);

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === $this->webhookUrl
                && str_contains($request->body(), 'New meal choice recorded!')
                && str_contains($request->body(), 'John Doe')
                && str_contains($request->body(), 'Pizza')
                && str_contains($request->body(), '2024-01-15')
                && str_contains($request->body(), '<@U12345>');
        });
    }

    public function test_notify_sends_message_for_updated_meal_choice(): void
    {
        Http::fake([
            $this->webhookUrl => Http::response(['ok' => true], 200),
        ]);

        $employee = Employee::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
            'choice' => 'Burger',
            'date' => '2024-01-16',
        ]);

        $result = $this->notifier->notify($employee, $mealChoice, false);

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->body(), 'Meal choice updated!')
                && str_contains($request->body(), 'Jane Smith');
        });
    }

    public function test_notify_mentions_user_when_slack_id_provided(): void
    {
        Http::fake([
            $this->webhookUrl => Http::response(['ok' => true], 200),
        ]);

        $employee = Employee::factory()->create([
            'slack_id' => 'U12345',
        ]);

        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->notifier->notify($employee, $mealChoice, true);

        Http::assertSent(function ($request) {
            return str_contains($request->body(), '<@U12345>');
        });
    }

    public function test_notify_sends_to_channel_when_no_slack_id(): void
    {
        Http::fake([
            $this->webhookUrl => Http::response(['ok' => true], 200),
        ]);

        $employee = Employee::factory()->create([
            'slack_id' => null,
        ]);

        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->notifier->notify($employee, $mealChoice, true);

        Http::assertSent(function ($request) {
            $body = $request->body();
            return !str_contains($body, '<@') && !str_contains($body, '#general');
        });
    }

    public function test_notify_handles_slack_id_with_at_prefix(): void
    {
        Http::fake([
            $this->webhookUrl => Http::response(['ok' => true], 200),
        ]);

        $employee = Employee::factory()->create([
            'slack_id' => '@U12345',
        ]);

        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->notifier->notify($employee, $mealChoice, true);

        Http::assertSent(function ($request) {
            return str_contains($request->body(), '<@U12345>');
        });
    }

    public function test_notify_handles_slack_id_with_hash_prefix(): void
    {
        Http::fake([
            $this->webhookUrl => Http::response(['ok' => true], 200),
        ]);

        $employee = Employee::factory()->create([
            'slack_id' => '#general',
        ]);

        $mealChoice = MealChoice::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->notifier->notify($employee, $mealChoice, true);

        Http::assertSent(function ($request) {
            $body = $request->body();
            return !str_contains($body, '<@');
        });
    }

    public function test_notify_returns_false_on_http_error(): void
    {
        Http::fake([
            $this->webhookUrl => Http::response(['error' => 'Bad Request'], 400),
        ]);

        $employee = Employee::factory()->create();
        $mealChoice = MealChoice::factory()->create(['employee_id' => $employee->id]);

        $result = $this->notifier->notify($employee, $mealChoice, true);

        $this->assertFalse($result);
    }

    public function test_notify_throws_exception_when_webhook_not_configured(): void
    {
        Config::set('services.slack.webhook_url', null);
        $notifier = new SlackNotifier();

        $employee = Employee::factory()->create();
        $mealChoice = MealChoice::factory()->create(['employee_id' => $employee->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Slack webhook URL is not configured');

        $notifier->notify($employee, $mealChoice, true);
    }

    public function test_notify_handles_network_exceptions_gracefully(): void
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        Log::shouldReceive('error')->once();

        $employee = Employee::factory()->create();
        $mealChoice = MealChoice::factory()->create(['employee_id' => $employee->id]);

        $result = $this->notifier->notify($employee, $mealChoice, true);

        $this->assertFalse($result);
    }

    public function test_send_test_message(): void
    {
        Http::fake([
            $this->webhookUrl => Http::response(['ok' => true], 200),
        ]);

        $result = $this->notifier->sendTestMessage('#general', 'Test message');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->body(), 'Test message');
        });
    }
}

