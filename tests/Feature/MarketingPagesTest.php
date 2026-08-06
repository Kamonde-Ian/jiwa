<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\InvestmentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(config('app.name'));
    }

    public function test_public_pages_render(): void
    {
        foreach (['public.about', 'public.plans', 'public.faq', 'public.contact', 'public.terms', 'public.privacy'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_home_redirects_authenticated_users_not_needed(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_plans_page_lists_active_plans(): void
    {
        InvestmentPlan::factory()->create(['name' => 'LivePlanX', 'is_active' => true, 'min_amount' => 75, 'daily_rate' => 0.01, 'duration_days' => 30]);

        $this->get(route('public.plans'))
            ->assertOk()
            ->assertSee('LivePlanX');
    }

    public function test_contact_form_submits(): void
    {
        $this->post(route('public.contact.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Question',
            'message' => 'How do I deposit?',
        ])->assertRedirect(route('public.contact'));

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Question',
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $this->post(route('public.contact.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_plans_page_shows_comparison_and_disclaimer(): void
    {
        $this->get(route('public.plans'))
            ->assertOk()
            ->assertSee('Compare')
            ->assertSee('Risk disclaimer');
    }

    public function test_calculator_computes_earnings(): void
    {
        $plan = InvestmentPlan::factory()->create([
            'name' => 'CalcPlan',
            'daily_rate' => 0.01,
            'duration_days' => 30,
            'min_amount' => 100,
            'is_active' => true,
        ]);

        \Livewire\Livewire::test('plan-calculator')
            ->set('selectedPlanId', $plan->id)
            ->set('amount', 1000)
            ->assertSee('$10.00')   // daily
            ->assertSee('$300.00')  // monthly
            ->assertSee('$300.00'); // profit at maturity
    }

    public function test_home_page_shows_testimonials_and_supported_assets(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Why investors choose')
            ->assertSee('investors say');
    }
}