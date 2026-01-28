<?php

namespace Tests\Feature\Controllers;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_returns_dashboard_with_domains(): void
    {
        Domain::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->has('domains', 3)
        );
    }

    public function test_index_only_returns_users_own_domains(): void
    {
        $otherUser = User::factory()->create();
        Domain::factory()->count(2)->create(['created_by' => $this->user->id]);
        Domain::factory()->count(3)->create(['created_by' => $otherUser->id]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page->has('domains', 2));
    }

    public function test_store_creates_domain(): void
    {
        $response = $this->actingAs($this->user)->post('/domains', [
            'hostname' => 'example.com',
            'method' => 'GET',
            'interval' => 60,
            'timeout' => 30,
            'is_active' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('domains', [
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post('/domains', []);

        $response->assertSessionHasErrors(['hostname']);
    }

    public function test_store_validates_hostname_uniqueness(): void
    {
        Domain::factory()->create([
            'hostname' => 'example.com',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post('/domains', [
            'hostname' => 'example.com',
            'method' => 'GET',
            'interval' => 60,
            'timeout' => 30,
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_show_returns_domain_as_json(): void
    {
        $domain = Domain::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/domains/{$domain->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $domain->id,
                'hostname' => $domain->hostname,
            ],
        ]);
    }

    public function test_show_returns_404_for_other_users_domain(): void
    {
        $otherUser = User::factory()->create();
        $domain = Domain::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/domains/{$domain->id}");

        $response->assertNotFound();
    }

    public function test_update_modifies_domain(): void
    {
        $domain = Domain::factory()->create([
            'hostname' => 'old.example.com',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->put("/domains/{$domain->id}", [
            'hostname' => 'new.example.com',
            'method' => 'HEAD',
            'interval' => 120,
            'timeout' => 60,
            'is_active' => false,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'hostname' => 'new.example.com',
            'method' => 'HEAD',
            'interval' => 120,
        ]);
    }

    public function test_update_returns_404_for_other_users_domain(): void
    {
        $otherUser = User::factory()->create();
        $domain = Domain::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->actingAs($this->user)->put("/domains/{$domain->id}", [
            'hostname' => 'hacked.com',
        ]);

        $response->assertNotFound();
    }

    public function test_destroy_deletes_domain(): void
    {
        $domain = Domain::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete("/domains/{$domain->id}");

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    public function test_destroy_returns_404_for_other_users_domain(): void
    {
        $otherUser = User::factory()->create();
        $domain = Domain::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->actingAs($this->user)->delete("/domains/{$domain->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('domains', ['id' => $domain->id]);
    }

    public function test_toggle_active_changes_status(): void
    {
        $domain = Domain::factory()->create([
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post("/domains/{$domain->id}/toggle-active");

        $response->assertRedirect('/dashboard');
        $this->assertFalse($domain->fresh()->is_active);
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}