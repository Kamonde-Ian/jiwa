<?php

namespace Tests\Feature;

use App\Domain\Wallets\WalletService;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralsTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_page_renders_with_link_when_qualified(): void
    {
        $user = User::factory()->create(['referral_code' => 'QUALIFY1']);

        app(WalletService::class)->credit(
            app(WalletService::class)->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
            1000,
            'Deposit',
        );

        \App\Models\Investment::create([
            'user_id' => $user->id,
            'plan_id' => \App\Models\InvestmentPlan::factory()->create()->id,
            'principal_amount' => 200,
            'daily_rate_snapshot' => 0.005,
            'status' => \App\Models\Investment::STATUS_ACTIVE,
            'starts_at' => now(),
            'matures_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->get('/referrals')
            ->assertOk()
            ->assertSee('ref=QUALIFY1')
            ->assertSee('Your referral link is unlocked');
    }

    public function test_referral_page_shows_locked_state_when_unqualified(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/referrals')
            ->assertOk()
            ->assertSee('Your referral link is locked');
    }

    public function test_referral_page_requires_auth(): void
    {
        $this->get('/referrals')->assertRedirect('/login');
    }
}
