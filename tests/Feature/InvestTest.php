<?php

namespace Tests\Feature;

use App\Domain\Wallets\WalletService;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\InvestmentPlanSeeder::class);
    }

    protected function fundedUser(float $amount = 1000): User
    {
        $user = User::factory()->create();
        app(WalletService::class)->credit(
            app(WalletService::class)->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
            $amount,
            'Deposit',
        );

        return $user;
    }

    public function test_invest_page_lists_active_plans(): void
    {
        $user = $this->fundedUser();

        $this->actingAs($user)
            ->get('/invest')
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('Elite');
    }

    public function test_user_can_invest(): void
    {
        $user = $this->fundedUser(500);
        $plan = InvestmentPlan::where('name', 'Starter')->first();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invest::class)
            ->set('selectedPlanId', $plan->id)
            ->set('amount', 200)
            ->call('invest')
            ->assertHasNoErrors()
            ->assertSet('amount', null)
            ->assertSet('selectedPlanId', 0);

        $this->assertDatabaseHas('investments', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->assertEquals(
            300,
            (float) app(WalletService::class)->getOrCreate($user, Wallet::TYPE_PRINCIPAL)->balance,
        );
    }

    public function test_invest_rejects_amount_exceeding_principal_balance(): void
    {
        $user = $this->fundedUser(100);
        $plan = InvestmentPlan::where('name', 'Starter')->first();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invest::class)
            ->set('selectedPlanId', $plan->id)
            ->set('amount', 500)
            ->call('invest')
            ->assertHasErrors('amount');
    }

    public function test_invest_rejects_below_minimum(): void
    {
        $user = $this->fundedUser(1000);
        $plan = InvestmentPlan::where('name', 'Starter')->first();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Invest::class)
            ->set('selectedPlanId', $plan->id)
            ->set('amount', 10)
            ->call('invest')
            ->assertHasErrors('amount');
    }

    public function test_invest_requires_login(): void
    {
        $this->get('/invest')->assertRedirect('/login');
    }
}
