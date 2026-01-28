<?php

namespace Tests\Feature\Services;

use App\DTO\Domain\CreateDomainDTO;
use App\DTO\Domain\UpdateDomainDTO;
use App\Exceptions\Domain\DomainAlreadyExistsException;
use App\Exceptions\Domain\DomainNotFoundException;
use App\Models\Domain;
use App\Models\User;
use App\Services\DomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainServiceTest extends TestCase
{
    use RefreshDatabase;

    private DomainService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DomainService::class);
        $this->user = User::factory()->create();
    }

    public function test_get_all_for_user_returns_only_users_domains(): void
    {
        $otherUser = User::factory()->create();

        Domain::factory()->count(3)->create(['created_by' => $this->user->id]);
        Domain::factory()->count(2)->create(['created_by' => $otherUser->id]);

        $domains = $this->service->getAllForUser($this->user);

        $this->assertCount(3, $domains);
        $domains->each(fn ($domain) => $this->assertEquals($this->user->id, $domain->created_by));
    }

    public function test_get_all_for_user_loads_latest_check(): void
    {
        $domain = Domain::factory()->create(['created_by' => $this->user->id]);
        $domain->checks()->create([
            'result' => 'SUCCESS',
            'response_code' => 200,
            'response_time_ms' => 150,
            'checked_at' => now(),
        ]);

        $domains = $this->service->getAllForUser($this->user);

        $this->assertTrue($domains->first()->relationLoaded('latestCheck'));
        $this->assertNotNull($domains->first()->latestCheck);
    }

    public function test_get_by_id_returns_domain_with_checks(): void
    {
        $domain = Domain::factory()->create(['created_by' => $this->user->id]);
        $domain->checks()->create([
            'result' => 'SUCCESS',
            'response_code' => 200,
            'response_time_ms' => 150,
            'checked_at' => now(),
        ]);

        $result = $this->service->getById($domain->id, $this->user);

        $this->assertEquals($domain->id, $result->id);
        $this->assertTrue($result->relationLoaded('checks'));
        $this->assertTrue($result->relationLoaded('latestCheck'));
    }

    public function test_get_by_id_throws_exception_for_other_users_domain(): void
    {
        $otherUser = User::factory()->create();
        $domain = Domain::factory()->create(['created_by' => $otherUser->id]);

        $this->expectException(DomainNotFoundException::class);
        $this->service->getById($domain->id, $this->user);
    }

    public function test_get_by_id_throws_exception_for_nonexistent_domain(): void
    {
        $this->expectException(DomainNotFoundException::class);
        $this->service->getById(99999, $this->user);
    }

    public function test_create_stores_domain(): void
    {
        $data = new CreateDomainDTO(
            hostname: 'example.com',
            method: 'GET',
            interval: 60,
            timeout: 30,
            body: null,
            is_active: true,
        );

        $domain = $this->service->create($data, $this->user);

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'hostname' => 'example.com',
            'method' => 'GET',
            'interval' => 60,
            'timeout' => 30,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_throws_exception_for_duplicate_hostname(): void
    {
        Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        $data = new CreateDomainDTO(
            hostname: 'example.com',
            method: 'GET',
            interval: 60,
            timeout: 30,
            body: null,
            is_active: true,
        );

        $this->expectException(DomainAlreadyExistsException::class);
        $this->service->create($data, $this->user);
    }

    public function test_create_allows_same_hostname_for_different_users(): void
    {
        $otherUser = User::factory()->create();
        Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $otherUser->id,
        ]);

        $data = new CreateDomainDTO(
            hostname: 'example.com',
            method: 'GET',
            interval: 60,
            timeout: 30,
            body: null,
            is_active: true,
        );

        $domain = $this->service->create($data, $this->user);

        $this->assertEquals('example.com', $domain->hostname);
        $this->assertEquals($this->user->id, $domain->created_by);
    }

    public function test_update_modifies_domain(): void
    {
        $domain = Domain::factory()->create([
            'hostname' => 'old.example.com',
            'interval' => 60,
            'created_by' => $this->user->id,
        ]);

        $data = new UpdateDomainDTO(
            hostname: 'new.example.com',
            interval: 120,
        );

        $updated = $this->service->update($domain->id, $data, $this->user);

        $this->assertEquals('new.example.com', $updated->hostname);
        $this->assertEquals(120, $updated->interval);
    }

    public function test_update_throws_exception_for_duplicate_hostname(): void
    {
        Domain::factory()->create([
            'hostname' => 'existing.com',
            'created_by' => $this->user->id,
        ]);

        $domain = Domain::factory()->create([
            'hostname' => 'original.com',
            'created_by' => $this->user->id,
        ]);

        $data = new UpdateDomainDTO(
            hostname: 'existing.com',
        );

        $this->expectException(DomainAlreadyExistsException::class);
        $this->service->update($domain->id, $data, $this->user);
    }

    public function test_delete_removes_domain(): void
    {
        $domain = Domain::factory()->create(['created_by' => $this->user->id]);

        $this->service->delete($domain->id, $this->user);

        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    public function test_delete_throws_exception_for_other_users_domain(): void
    {
        $otherUser = User::factory()->create();
        $domain = Domain::factory()->create(['created_by' => $otherUser->id]);

        $this->expectException(DomainNotFoundException::class);
        $this->service->delete($domain->id, $this->user);
    }

    public function test_toggle_active_changes_status(): void
    {
        $domain = Domain::factory()->create([
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $result = $this->service->toggleActive($domain->id, $this->user);

        $this->assertFalse($result->is_active);

        $result = $this->service->toggleActive($domain->id, $this->user);

        $this->assertTrue($result->is_active);
    }
}