<?php

namespace Tests\Feature\Services;

use App\Enums\CheckResult;
use App\Models\Domain;
use App\Models\DomainCheck;
use App\Models\User;
use App\Notifications\DomainDownNotification;
use App\Notifications\DomainUpNotification;
use App\Services\DomainCheckerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DomainCheckerServiceTest extends TestCase
{
    use RefreshDatabase;

    private DomainCheckerService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DomainCheckerService::class);
        $this->user = User::factory()->create();
    }

    public function test_check_creates_success_result_for_200_response(): void
    {
        Http::fake([
            'https://example.com' => Http::response('OK', 200),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        $check = $this->service->check($domain);

        $this->assertEquals(CheckResult::SUCCESS, $check->result);
        $this->assertEquals(200, $check->response_code);
        $this->assertNotNull($check->response_time_ms);
        $this->assertNull($check->error_message);
    }

    public function test_check_creates_fail_result_for_500_response(): void
    {
        Http::fake([
            'https://example.com' => Http::response('Error', 500),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        $check = $this->service->check($domain);

        $this->assertEquals(CheckResult::FAIL, $check->result);
        $this->assertEquals(500, $check->response_code);
    }

    public function test_check_creates_timeout_result_for_timeout_exception(): void
    {
        Http::fake([
            'https://example.com' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection timed out'),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        $check = $this->service->check($domain);

        $this->assertEquals(CheckResult::TIMEOUT, $check->result);
        $this->assertNull($check->response_code);
        $this->assertStringContainsString('timed out', $check->error_message);
    }

    public function test_check_creates_dns_error_for_resolution_failure(): void
    {
        Http::fake([
            'https://nonexistent.invalid' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host'),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'nonexistent.invalid',
            'created_by' => $this->user->id,
        ]);

        $check = $this->service->check($domain);

        $this->assertEquals(CheckResult::DNS_ERROR, $check->result);
    }

    public function test_check_updates_last_checked_at(): void
    {
        Http::fake([
            'https://example.com' => Http::response('OK', 200),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
            'last_checked_at' => null,
        ]);

        $this->service->check($domain);

        $domain->refresh();
        $this->assertNotNull($domain->last_checked_at);
    }

    public function test_check_adds_https_prefix_if_missing(): void
    {
        Http::fake([
            'https://example.com' => Http::response('OK', 200),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        $check = $this->service->check($domain);

        $this->assertEquals(CheckResult::SUCCESS, $check->result);
        Http::assertSent(fn ($request) => $request->url() === 'https://example.com');
    }

    public function test_check_preserves_http_prefix(): void
    {
        Http::fake([
            'http://example.com' => Http::response('OK', 200),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'http://example.com',
            'created_by' => $this->user->id,
        ]);

        $check = $this->service->check($domain);

        Http::assertSent(fn ($request) => $request->url() === 'http://example.com');
    }

    public function test_sends_notification_when_domain_goes_down(): void
    {
        Notification::fake();

        Http::fake([
            'https://example.com' => Http::response('Error', 500),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        // Create a previous successful check
        DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'result' => CheckResult::SUCCESS,
            'checked_at' => now()->subMinutes(5),
        ]);

        // Reload to get the latestCheck relationship
        $domain->load('latestCheck');

        $this->service->check($domain);

        Notification::assertSentTo($this->user, DomainDownNotification::class);
    }

    public function test_sends_notification_when_domain_recovers(): void
    {
        Notification::fake();

        Http::fake([
            'https://example.com' => Http::response('OK', 200),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        // Create a previous failed check
        DomainCheck::factory()->failed()->create([
            'domain_id' => $domain->id,
            'checked_at' => now()->subMinutes(5),
        ]);

        $domain->load('latestCheck');

        $this->service->check($domain);

        Notification::assertSentTo($this->user, DomainUpNotification::class);
    }

    public function test_does_not_send_notification_when_status_unchanged(): void
    {
        Notification::fake();

        Http::fake([
            'https://example.com' => Http::response('OK', 200),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        // Create a previous successful check
        DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'result' => CheckResult::SUCCESS,
            'checked_at' => now()->subMinutes(5),
        ]);

        $domain->load('latestCheck');

        $this->service->check($domain);

        Notification::assertNothingSent();
    }

    public function test_sends_down_notification_on_first_failed_check(): void
    {
        Notification::fake();

        Http::fake([
            'https://example.com' => Http::response('Error', 500),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        // No previous checks - domain is assumed to be "up"
        $this->service->check($domain);

        Notification::assertSentTo($this->user, DomainDownNotification::class);
    }

    public function test_does_not_send_notification_on_first_successful_check(): void
    {
        Notification::fake();

        Http::fake([
            'https://example.com' => Http::response('OK', 200),
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        $this->service->check($domain);

        Notification::assertNothingSent();
    }
}