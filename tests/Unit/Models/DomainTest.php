<?php

namespace Tests\Unit\Models;

use App\Enums\CheckResult;
use App\Models\Domain;
use App\Models\DomainCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_active_returns_only_active_domains(): void
    {
        Domain::factory()->count(3)->create(['is_active' => true]);
        Domain::factory()->count(2)->create(['is_active' => false]);

        $activeDomains = Domain::active()->get();

        $this->assertCount(3, $activeDomains);
        $activeDomains->each(fn ($d) => $this->assertTrue($d->is_active));
    }

    public function test_scope_needs_check_returns_domains_never_checked(): void
    {
        Domain::factory()->create([
            'is_active' => true,
            'last_checked_at' => null,
        ]);

        $domains = Domain::needsCheck()->get();

        $this->assertCount(1, $domains);
    }

    public function test_scope_needs_check_returns_domains_past_interval(): void
    {
        Domain::factory()->create([
            'is_active' => true,
            'interval' => 60,
            'last_checked_at' => now()->subSeconds(120),
        ]);

        $domains = Domain::needsCheck()->get();

        $this->assertCount(1, $domains);
    }

    public function test_scope_needs_check_excludes_recently_checked_domains(): void
    {
        Domain::factory()->create([
            'is_active' => true,
            'interval' => 60,
            'last_checked_at' => now()->subSeconds(30),
        ]);

        $domains = Domain::needsCheck()->get();

        $this->assertCount(0, $domains);
    }

    public function test_scope_needs_check_excludes_inactive_domains(): void
    {
        Domain::factory()->create([
            'is_active' => false,
            'last_checked_at' => null,
        ]);

        $domains = Domain::needsCheck()->get();

        $this->assertCount(0, $domains);
    }

    public function test_mark_as_checked_updates_timestamp(): void
    {
        $domain = Domain::factory()->create(['last_checked_at' => null]);

        $domain->markAsChecked();

        $this->assertNotNull($domain->fresh()->last_checked_at);
    }

    public function test_is_currently_down_returns_true_when_latest_check_failed(): void
    {
        $domain = Domain::factory()->create();
        DomainCheck::factory()->failed()->create([
            'domain_id' => $domain->id,
            'checked_at' => now(),
        ]);

        $domain->load('latestCheck');

        $this->assertTrue($domain->isCurrentlyDown());
    }

    public function test_is_currently_down_returns_false_when_latest_check_succeeded(): void
    {
        $domain = Domain::factory()->create();
        DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'result' => CheckResult::SUCCESS,
            'checked_at' => now(),
        ]);

        $domain->load('latestCheck');

        $this->assertFalse($domain->isCurrentlyDown());
    }

    public function test_is_currently_down_returns_false_when_no_checks(): void
    {
        $domain = Domain::factory()->create();

        $this->assertFalse($domain->isCurrentlyDown());
    }

    public function test_get_uptime_percentage_calculates_correctly(): void
    {
        $domain = Domain::factory()->create();

        // 8 successful, 2 failed = 80% uptime
        DomainCheck::factory()->count(8)->create([
            'domain_id' => $domain->id,
            'result' => CheckResult::SUCCESS,
            'checked_at' => now()->subHours(1),
        ]);

        DomainCheck::factory()->count(2)->failed()->create([
            'domain_id' => $domain->id,
            'checked_at' => now()->subHours(1),
        ]);

        $uptime = $domain->getUptimePercentage(24);

        $this->assertEquals(80.0, $uptime);
    }

    public function test_get_uptime_percentage_returns_null_when_no_checks(): void
    {
        $domain = Domain::factory()->create();

        $uptime = $domain->getUptimePercentage(24);

        $this->assertNull($uptime);
    }

    public function test_get_uptime_percentage_excludes_old_checks(): void
    {
        $domain = Domain::factory()->create();

        // Old check (outside 24h window)
        DomainCheck::factory()->failed()->create([
            'domain_id' => $domain->id,
            'checked_at' => now()->subHours(48),
        ]);

        // Recent check
        DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'result' => CheckResult::SUCCESS,
            'checked_at' => now()->subHours(1),
        ]);

        $uptime = $domain->getUptimePercentage(24);

        $this->assertEquals(100.0, $uptime);
    }

    public function test_get_avg_response_time_calculates_correctly(): void
    {
        $domain = Domain::factory()->create();

        DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'response_time_ms' => 100,
            'checked_at' => now()->subHours(1),
        ]);

        DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'response_time_ms' => 200,
            'checked_at' => now()->subHours(1),
        ]);

        $avg = $domain->getAvgResponseTime(24);

        $this->assertEquals(150, $avg);
    }

    public function test_get_avg_response_time_excludes_null_values(): void
    {
        $domain = Domain::factory()->create();

        DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'response_time_ms' => 100,
            'checked_at' => now()->subHours(1),
        ]);

        DomainCheck::factory()->timeout()->create([
            'domain_id' => $domain->id,
            'checked_at' => now()->subHours(1),
        ]);

        $avg = $domain->getAvgResponseTime(24);

        $this->assertEquals(100, $avg);
    }

    public function test_creator_relationship(): void
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['created_by' => $user->id]);

        $this->assertEquals($user->id, $domain->creator->id);
    }

    public function test_checks_relationship(): void
    {
        $domain = Domain::factory()->create();
        DomainCheck::factory()->count(5)->create(['domain_id' => $domain->id]);

        $this->assertCount(5, $domain->checks);
    }

    public function test_latest_check_returns_most_recent(): void
    {
        $domain = Domain::factory()->create();

        DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'checked_at' => now()->subHours(2),
        ]);

        $latestCheck = DomainCheck::factory()->create([
            'domain_id' => $domain->id,
            'checked_at' => now(),
        ]);

        $domain->load('latestCheck');

        $this->assertEquals($latestCheck->id, $domain->latestCheck->id);
    }
}