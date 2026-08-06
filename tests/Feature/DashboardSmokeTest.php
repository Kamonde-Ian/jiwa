<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_sneat_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Growth Overview')
            ->assertSee('Total Balance')
            ->assertSee('logout');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_statements_page_renders_inside_dashboard_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/statements')
            ->assertOk()
            ->assertSee('Statement');
    }

    public function test_investments_page_renders_inside_dashboard_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/investments')
            ->assertOk()
            ->assertSee('Investment History');
    }
}
