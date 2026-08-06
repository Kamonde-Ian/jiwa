<?php

namespace Tests\Feature;

use App\Domain\Wallets\WalletService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Support\TwoFactorAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WithdrawalsTest extends TestCase
{
    use RefreshDatabase;

    protected function twoFactorUser(): User
    {
        $twoFactor = app(TwoFactorAuth::class);
        $secret = $twoFactor->generateSecret();

        $user = User::factory()->kycVerified()->withTwoFactor($secret)->create();

        app(WalletService::class)->credit(
            app(WalletService::class)->getOrCreate($user, Wallet::TYPE_EARNINGS),
            500,
            'Interest',
        );

        return $user;
    }

    protected function otpFor(User $user): string
    {
        return app(\PragmaRX\Google2FALaravel\Google2FA::class)
            ->getCurrentOtp((string) $user->google2fa_secret);
    }

    public function test_withdrawal_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/withdrawals')
            ->assertOk()
            ->assertSee('Request a Withdrawal');
    }

    public function test_user_can_request_withdrawal(): void
    {
        $user = $this->twoFactorUser();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Withdrawals::class)
            ->set('wallet_type', 'earnings')
            ->set('network', 'usdt_trc20')
            ->set('destination_address', 'TAddr123')
            ->set('amount', 100)
            ->set('otp', $this->otpFor($user))
            ->call('request')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'wallet_type' => 'earnings',
            'amount' => 100,
            'status' => Withdrawal::STATUS_APPROVED,
        ]);
    }

    public function test_withdrawal_rejects_invalid_otp(): void
    {
        $user = $this->twoFactorUser();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Withdrawals::class)
            ->set('wallet_type', 'earnings')
            ->set('network', 'usdt_trc20')
            ->set('destination_address', 'TAddr123')
            ->set('amount', 100)
            ->set('otp', '000000')
            ->call('request')
            ->assertHasErrors('otp');

        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_withdrawal_page_requires_auth(): void
    {
        $this->get('/withdrawals')->assertRedirect('/login');
    }
}
