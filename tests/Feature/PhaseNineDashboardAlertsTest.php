<?php

namespace Tests\Feature;

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
}
