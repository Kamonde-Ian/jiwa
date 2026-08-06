<?php

namespace Tests\Unit;

use App\Domain\Wallets\WalletService;
use App\Domain\Withdrawals\WithdrawalService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Support\TwoFactorAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WithdrawalService $service;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WithdrawalService::class);
        $this->walletService = app(WalletService::class);
    }

    protected function twoFactorUser(float $balance = 500): User
    {
        $twoFactor = app(TwoFactorAuth::class);
        $secret = $twoFactor->generateSecret();

        $user = User::factory()->kycVerified()->withTwoFactor($secret)->create();

        $this->walletService->credit(
            $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS),
            $balance,
            'Interest',
        );

        return $user;
    }

    protected function otpFor(User $user): string
    {
        return app(\PragmaRX\Google2FALaravel\Google2FA::class)
            ->getCurrentOtp((string) $user->google2fa_secret);
    }

    public function test_request_freezes_funds_and_auto_approves_below_threshold(): void
    {
        $user = $this->twoFactorUser(500);

        $withdrawal = $this->service->request(
            $user,
            Wallet::TYPE_EARNINGS,
            100,
            'usdt_trc20',
            'TAddr123',
            $this->otpFor($user),
        );

        $this->assertEquals(Withdrawal::STATUS_APPROVED, $withdrawal->status);
        $this->assertEquals(100, (float) $withdrawal->amount);

        $this->assertEquals(
            400,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_request_above_threshold_requires_review(): void
    {
        config(['jiwa.withdrawal_auto_approve_threshold' => 50]);
        $user = $this->twoFactorUser(1000);

        $withdrawal = $this->service->request(
            $user,
            Wallet::TYPE_EARNINGS,
            100,
            'btc',
            'bc1q123',
            $this->otpFor($user),
        );

        $this->assertEquals(Withdrawal::STATUS_PENDING_REVIEW, $withdrawal->status);
    }

    public function test_request_requires_kyc(): void
    {
        $twoFactor = app(TwoFactorAuth::class);
        $secret = $twoFactor->generateSecret();
        $user = User::factory()->withTwoFactor($secret)->create(['kyc_status' => User::KYC_UNVERIFIED]);

        $this->walletService->credit(
            $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS),
            100,
            'Interest',
        );

        $this->expectException(\InvalidArgumentException::class);

        $this->service->request($user, Wallet::TYPE_EARNINGS, 50, 'btc', 'addr', $this->otpFor($user));
    }

    public function test_request_requires_valid_2fa_code(): void
    {
        $user = $this->twoFactorUser(500);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->request($user, Wallet::TYPE_EARNINGS, 50, 'btc', 'addr', '000000');
    }

    public function test_request_requires_2fa_enabled(): void
    {
        $user = User::factory()->kycVerified()->create(['two_factor_enabled' => false]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->request($user, Wallet::TYPE_EARNINGS, 50, 'btc', 'addr', '000000');
    }

    public function test_request_rejects_amount_below_minimum(): void
    {
        $user = $this->twoFactorUser(500);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->request($user, Wallet::TYPE_EARNINGS, 5, 'btc', 'addr', $this->otpFor($user));
    }

    public function test_principal_withdrawal_locked_while_investment_active(): void
    {
        $user = $this->twoFactorUser();
        $this->walletService->credit(
            $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
            500,
            'Deposit',
        );

        \App\Models\Investment::create([
            'user_id' => $user->id,
            'plan_id' => \App\Models\InvestmentPlan::factory()->create()->id,
            'principal_amount' => 100,
            'daily_rate_snapshot' => 0.005,
            'status' => \App\Models\Investment::STATUS_ACTIVE,
            'starts_at' => now(),
            'matures_at' => now()->addDays(30),
        ]);

        $this->expectException(\App\Domain\Wallets\Exceptions\PrincipalWalletLockedException::class);

        $this->service->request($user, Wallet::TYPE_PRINCIPAL, 50, 'btc', 'addr', $this->otpFor($user));
    }

    public function test_overlapping_requests_cannot_overdraw(): void
    {
        $user = $this->twoFactorUser(300);

        $this->service->request($user, Wallet::TYPE_EARNINGS, 200, 'btc', 'a', $this->otpFor($user));

        $this->expectException(\App\Domain\Wallets\Exceptions\InsufficientBalanceException::class);

        $this->service->request($user, Wallet::TYPE_EARNINGS, 200, 'btc', 'b', $this->otpFor($user));
    }

    public function test_approve_then_complete(): void
    {
        $user = $this->twoFactorUser(1000);
        $admin = User::factory()->create();

        $withdrawal = $this->service->request(
            $user,
            Wallet::TYPE_EARNINGS,
            200,
            'usdt_erc20',
            '0x123',
            $this->otpFor($user),
        );

        $this->service->approve($withdrawal, $admin, 'OK');
        $this->assertEquals(Withdrawal::STATUS_APPROVED, $withdrawal->fresh()->status);

        $this->service->complete($withdrawal->fresh(), $admin);
        $this->assertEquals(Withdrawal::STATUS_COMPLETED, $withdrawal->fresh()->status);

        // Funds stay frozen.
        $this->assertEquals(
            800,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_reject_refunds_funds(): void
    {
        $user = $this->twoFactorUser(500);
        $admin = User::factory()->create();

        $withdrawal = $this->service->request(
            $user,
            Wallet::TYPE_EARNINGS,
            100,
            'btc',
            'addr',
            $this->otpFor($user),
        );

        $this->service->reject($withdrawal, $admin, 'Suspicious');

        $this->assertEquals(Withdrawal::STATUS_REJECTED, $withdrawal->fresh()->status);

        $this->assertEquals(
            500,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_user_can_cancel_own_pending_withdrawal(): void
    {
        config(['jiwa.withdrawal_auto_approve_threshold' => 50]);
        $user = $this->twoFactorUser(500);

        $withdrawal = $this->service->request(
            $user,
            Wallet::TYPE_EARNINGS,
            100,
            'btc',
            'addr',
            $this->otpFor($user),
        );

        $this->assertEquals(Withdrawal::STATUS_PENDING_REVIEW, $withdrawal->status);

        $this->service->cancel($withdrawal, $user);

        $this->assertEquals(Withdrawal::STATUS_CANCELLED, $withdrawal->fresh()->status);

        $this->assertEquals(
            500,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_user_cannot_cancel_another_users_withdrawal(): void
    {
        config(['jiwa.withdrawal_auto_approve_threshold' => 50]);
        $user = $this->twoFactorUser(500);
        $other = User::factory()->create();

        $withdrawal = $this->service->request(
            $user,
            Wallet::TYPE_EARNINGS,
            100,
            'btc',
            'addr',
            $this->otpFor($user),
        );

        $this->expectException(\LogicException::class);

        $this->service->cancel($withdrawal, $other);
    }
}
