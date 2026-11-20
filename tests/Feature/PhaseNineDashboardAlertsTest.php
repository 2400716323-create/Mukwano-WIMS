<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseNineDashboardAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_when_optional_filters_are_omitted(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
    }

    public function test_administrator_can_open_alert_centre_and_see_expiry_overview(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMINISTRATOR,
        ]);

        foreach (['critical', 'high', 'medium', 'low'] as $severity) {
            Alert::create([
                'condition_key' => 'expiry-test-' . $severity,
                'type' => 'expiry',
                'severity' => $severity,
                'title' => 'Test ' . $severity . ' expiry alert',
                'description' => 'Test expiry description',
                'condition_active' => true,
                'last_seen_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('alerts.index'));

        $response->assertOk()
            ->assertSee('Near-Expiry Overview')
            ->assertSee('Expired Stock')
            ->assertSee('Within 30 Days')
            ->assertSee('Within 60 Days')
            ->assertSee('Within 90 Days');
    }

    public function test_expiry_filter_shows_only_expiry_alerts(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMINISTRATOR,
        ]);

        Alert::create([
            'condition_key' => 'expiry-filter-test',
            'type' => 'expiry',
            'severity' => 'high',
            'title' => 'Expiry Alert Example',
            'description' => 'Expiry test',
            'condition_active' => true,
            'last_seen_at' => now(),
        ]);

        Alert::create([
            'condition_key' => 'stock-filter-test',
            'type' => 'low_stock',
            'severity' => 'high',
            'title' => 'Low Stock Alert Example',
            'description' => 'Low stock test',
            'condition_active' => true,
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('alerts.index', [
            'type' => 'expiry',
            'state' => 'open',
        ]));

        $response->assertOk()
            ->assertSee('Expiry Alert Example')
            ->assertDontSee('Low Stock Alert Example');
    }

    public function test_administrator_can_resolve_an_active_alert(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMINISTRATOR,
        ]);

        $alert = Alert::create([
            'condition_key' => 'resolve-test',
            'type' => 'low_stock',
            'severity' => 'high',
            'title' => 'Alert to Resolve',
            'description' => 'Test resolution',
            'condition_active' => true,
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($admin)->patch(
            route('alerts.resolve', $alert)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'resolved_by' => $admin->id,
        ]);

        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_storekeeper_cannot_resolve_an_alert(): void
    {
        $storekeeper = User::factory()->create([
            'role' => 'storekeeper',
        ]);

        $alert = Alert::create([
            'condition_key' => 'unauthorized-resolve-test',
            'type' => 'low_stock',
            'severity' => 'high',
            'title' => 'Protected Alert',
            'description' => 'Test access control',
            'condition_active' => true,
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($storekeeper)->patch(
            route('alerts.resolve', $alert)
        );

        $response->assertForbidden();

        $this->assertNull($alert->fresh()->resolved_at);
    }
}
