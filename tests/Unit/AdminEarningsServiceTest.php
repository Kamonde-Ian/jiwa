<?php

namespace Tests\Unit;

use App\Domain\AdminEarnings\AdminEarningsService;
use App\Domain\Deposits\DepositService;
use App\Domain\Investments\InvestmentService;
use App\Domain\Wallets\WalletService;
use App\Domain\Withdrawals\WithdrawalService;
use App\Models\Deposit;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEarningsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminEarningsService $service;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AdminEarningsService::class);
        $this->walletService = app(WalletService::class);

        // Grant the admin role to three users so the equal split targets them.
        $this->seedAdmins(3);
    }

    protected function seedAdmins(int $count): void
    {
        $role = \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('view admin earnings', 'web');
        $role->givePermissionTo('view admin earnings');

        for ($i = 0; $i < $count; $i++) {
            User::factory()->create()->assignRole('admin');
        }
    }

    protected function adminIds(): array
    {
        return app(AdminEarningsService::class)->admins()->pluck('id')->all();
    }

    protected function confirmedDeposit(float $amount): Deposit
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $deposit = app(DepositService::class)->request($user, 'eth', uniqid('tx_', true), $amount);

        return app(DepositService::class)->confirm($deposit, $admin);
    }

    protected function completedWithdrawal(float $amount): Withdrawal
    {
        $user = User::factory()->create();
        $this->walletService->credit(
            $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS),
            $amount,
            'Test funding',
        );

        $secret = app(\PragmaRX\Google2FALaravel\Google2FA::class)->generateSecretKey();
        $user->update(['two_factor_enabled' => true, 'google2fa_secret' => $secret, 'kyc_status' => User::KYC_VERIFIED]);

        $otp = app(\PragmaRX\Google2FALaravel\Google2FA::class)->getCurrentOtp($secret);

        $withdrawal = app(WithdrawalService::class)->request(
            $user,
            Wallet::TYPE_EARNINGS,
            $amount,
            'btc',
            'bc1test',
            $otp,
        );

        if ($withdrawal->status === Withdrawal::STATUS_PENDING_REVIEW) {
            $admin = User::factory()->create();
            app(WithdrawalService::class)->approve($withdrawal, $admin);
        }

        return app(WithdrawalService::class)->complete($withdrawal, User::factory()->create());
    }

    protected function activeInvestment(float $amount): \App\Models\Investment
    {
        $user = User::factory()->create();
        $this->walletService->credit(
            $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
            $amount,
            'Test funding',
        );

        return app(InvestmentService::class)->create($user, InvestmentPlan::factory()->create(), $amount);
    }

    public function test_distributes_deposits_equally_among_admins(): void
    {
        config(['jiwa.admin_earnings_rate' => 0.02]);

        $deposit = $this->confirmedDeposit(1000);

        $result = $this->service->distributePending();

        $this->assertSame(1, $result['deposits']);
        $this->assertSame(0, $result['withdrawals']);
        $this->assertSame(0, $result['investments']);
        $this->assertEqualsWithDelta(20.0, $result['total'], 0.001);

        // 2% of $1000 = $20 split among 3 admins ≈ $6.67 each.
        foreach ($this->adminIds() as $adminId) {
            $earning = \App\Models\AdminEarning::where('source_type', Deposit::class)
                ->where('source_id', $deposit->id)
                ->where('admin_id', $adminId)
                ->first();

            $this->assertNotNull($earning);
            $this->assertEqualsWithDelta(6.67, (float) $earning->amount, 0.01);
            $this->assertEquals(0.02, (float) $earning->rate);

            $this->assertEqualsWithDelta(
                (float) $earning->amount,
                (float) $this->walletService->getOrCreate(User::find($adminId), Wallet::TYPE_ADMIN_EARNINGS)->balance,
                0.001,
            );
        }

        // Sum of the three shares equals the full 2% commission.
        $totalShares = \App\Models\AdminEarning::where('source_type', Deposit::class)
            ->where('source_id', $deposit->id)
            ->sum('amount');

        $this->assertEqualsWithDelta(20.0, (float) $totalShares, 0.01);
    }

    public function test_distributes_withdrawals_and_investments(): void
    {
        $this->completedWithdrawal(1000);
        $this->activeInvestment(2000);

        $result = $this->service->distributePending();

        $this->assertSame(0, $result['deposits']);
        $this->assertSame(1, $result['withdrawals']);
        $this->assertSame(1, $result['investments']);

        // 2% of 1000 + 2% of 2000 = 20 + 40 = 60
        $this->assertEqualsWithDelta(60.0, $result['total'], 0.001);

        $this->assertSame(3, \App\Models\AdminEarning::where('source_type', Withdrawal::class)->count());
        $this->assertSame(3, \App\Models\AdminEarning::where('source_type', \App\Models\Investment::class)->count());
    }

    public function test_distribution_is_idempotent(): void
    {
        $this->confirmedDeposit(1000);

        $this->service->distributePending();
        $firstTotal = \App\Models\AdminEarning::sum('amount');

        $result = $this->service->distributePending();

        $this->assertSame(0, $result['deposits']);
        $this->assertSame(0.0, $result['total']);
        $this->assertEqualsWithDelta($firstTotal, \App\Models\AdminEarning::sum('amount'), 0.001);

        foreach ($this->adminIds() as $adminId) {
            $this->assertEqualsWithDelta(
                6.67,
                (float) $this->walletService->getOrCreate(User::find($adminId), Wallet::TYPE_ADMIN_EARNINGS)->balance,
                0.01,
            );
        }
    }

    public function test_no_admins_means_no_distribution(): void
    {
        User::query()->delete();

        $this->confirmedDeposit(1000);

        $result = $this->service->distributePending();

        $this->assertSame(0, $result['admins']);
        $this->assertSame(0.0, $result['total']);
        $this->assertSame(0, \App\Models\AdminEarning::count());
    }

    public function test_disabled_feature_distributes_nothing(): void
    {
        config(['jiwa.admin_earnings_enabled' => false]);

        $this->confirmedDeposit(1000);

        $result = $this->service->distributePending();

        $this->assertSame(0, $result['deposits']);
        $this->assertSame(0.0, $result['total']);
        $this->assertSame(0, \App\Models\AdminEarning::count());
    }

    public function test_unconfirmed_sources_are_not_distributed(): void
    {
        $user = User::factory()->create();
        app(DepositService::class)->request($user, 'btc', uniqid('tx_', true), 500); // stays pending

        $result = $this->service->distributePending();

        $this->assertSame(0, $result['deposits']);
        $this->assertSame(0, \App\Models\AdminEarning::count());
    }

    public function test_rate_is_configurable(): void
    {
        config(['jiwa.admin_earnings_rate' => 0.05]);

        $this->confirmedDeposit(1000);

        $this->service->distributePending();

        // 5% of $1000 = $50 split equally.
        $totalShares = \App\Models\AdminEarning::sum('amount');
        $this->assertEqualsWithDelta(50.0, (float) $totalShares, 0.01);
    }
}