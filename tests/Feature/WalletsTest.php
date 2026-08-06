<?php

namespace Tests\Feature;

use App\Domain\Wallets\WalletService;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletsTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_dashboard_displays_wallets_and_transactions(): void
    {
        $user = User::factory()->create();
        $service = app(WalletService::class);

        $earnings = $service->getOrCreate($user, Wallet::TYPE_EARNINGS);
        $service->credit($earnings, 120.5, 'Daily interest');

        $response = $this->actingAs($user)->get('/wallets');

        $response
            ->assertOk()
            ->assertSee('Principal Wallet')
            ->assertSee('Earnings Wallet')
            ->assertSee('Referral Wallet')
            ->assertSee('Recent Transactions')
            ->assertSee('120.50');
    }

    public function test_wallet_dashboard_requires_auth(): void
    {
        $this->get('/wallets')->assertRedirect('/login');
    }
}
