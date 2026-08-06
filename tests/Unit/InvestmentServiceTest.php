<?php

namespace Tests\Unit;

use App\Domain\Investments\InvestmentService;
use App\Domain\Wallets\WalletService;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvestmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvestmentService $service;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvestmentService::class);
        $this->walletService = app(WalletService::class);
    }

    protected function fundedUser(float $amount = 1000): User
    {
        $user = User::factory()->create();
        $this->walletService->credit(
            $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
            $amount,
            'Deposit',
        );

        return $user;
    }

    protected function plan(float $rate = 0.005, int $days = 30, float $min = 50): InvestmentPlan
    {
        return InvestmentPlan::factory()->create([
            'daily_rate' => $rate,
            'duration_days' => $days,
            'min_amount' => $min,
        ]);
    }

    public function test_create_moves_funds_from_principal_wallet(): void
    {
        $user = $this->fundedUser(500);
        $plan = $this->plan();

        $investment = $this->service->create($user, $plan, 200);

        $this->assertDatabaseHas('investments', [
            'id' => $investment->id,
            'status' => Investment::STATUS_ACTIVE,
            'daily_rate_snapshot' => $plan->daily_rate,
        ]);

        $this->assertEquals(
            300,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL)->balance,
        );

        $this->assertEquals(
            0,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_create_snapshots_rate_and_sets_maturity(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(rate: 0.01, days: 90);

        $investment = $this->service->create($user, $plan, 100);

        $this->assertEquals(0.01, (float) $investment->daily_rate_snapshot);
        $this->assertEqualsWithDelta(
            90,
            $investment->starts_at->diffInDays($investment->matures_at),
            0.1,
        );
        $this->assertNotNull($investment->last_interest_credited_at);
    }

    public function test_create_rejects_amount_below_minimum(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(min: 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->create($user, $plan, 50);
    }

    public function test_create_rejects_inactive_plan(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan();
        $plan->update(['is_active' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->create($user, $plan, 100);
    }

    public function test_create_dispatches_investment_created_event(): void
    {
        Event::fake();

        $user = $this->fundedUser(1000);
        $plan = $this->plan();

        $investment = $this->service->create($user, $plan, 100);

        Event::assertDispatched(\App\Events\InvestmentCreated::class, fn ($e) => $e->investment->id === $investment->id);
    }

    public function test_interest_is_credited_after_window_elapses(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(rate: 0.005, days: 30);
        $investment = $this->service->create($user, $plan, 1000);

        $credited = $this->service->creditDailyInterest(CarbonImmutable::now()->addHours(24));

        $this->assertEquals(1, $credited);
        $this->assertEquals(
            5.0,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
        $this->assertEqualsWithDelta(
            CarbonImmutable::now()->addHours(24)->timestamp,
            $investment->fresh()->last_interest_credited_at->timestamp,
            2,
        );
    }

    public function test_running_twice_within_window_is_idempotent(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(rate: 0.005, days: 30);
        $this->service->create($user, $plan, 1000);

        $now = CarbonImmutable::now()->addHours(24);

        $first = $this->service->creditDailyInterest($now);
        $second = $this->service->creditDailyInterest($now);

        $this->assertEquals(1, $first);
        $this->assertEquals(0, $second);
        $this->assertEquals(
            5.0,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_multiple_credits_over_multiple_days(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(rate: 0.01, days: 30);
        $investment = $this->service->create($user, $plan, 1000);

        $now = CarbonImmutable::now()->addHours(24);
        $this->service->creditDailyInterest($now);
        $this->service->creditDailyInterest($now->addHours(24));
        $this->service->creditDailyInterest($now->addHours(48));

        $this->assertEquals(
            30.0,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
        $this->assertEquals(
            3,
            $investment->transactions()->count(),
        );
    }

    public function test_no_interest_before_window_elapses(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan();
        $this->service->create($user, $plan, 1000);

        $credited = $this->service->creditDailyInterest(CarbonImmutable::now()->addHours(23));

        $this->assertEquals(0, $credited);
        $this->assertEquals(
            0.0,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_no_interest_after_maturity(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(days: 2);
        $investment = $this->service->create($user, $plan, 1000);

        $credited = $this->service->creditDailyInterest(CarbonImmutable::now()->addDays(3));

        $this->assertEquals(0, $credited);
        $this->assertEquals(0.0, (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance);

        $this->service->processMaturities(CarbonImmutable::now()->addDays(3));

        $this->assertEquals(Investment::STATUS_MATURED, $investment->fresh()->status);
    }

    public function test_maturity_releases_principal_to_principal_wallet(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(days: 5);
        $investment = $this->service->create($user, $plan, 300);

        $matured = $this->service->processMaturities(CarbonImmutable::now()->addDays(6));

        $this->assertEquals(1, $matured);
        $this->assertEquals(Investment::STATUS_MATURED, $investment->fresh()->status);
        $this->assertEquals(
            1000,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL)->balance,
        );
        $this->assertEquals(
            0,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_maturity_releases_principal_to_earnings_when_configured(): void
    {
        config(['jiwa.matured_principal_destination' => 'earnings']);

        $user = $this->fundedUser(1000);
        $plan = $this->plan(days: 5);
        $investment = $this->service->create($user, $plan, 300);

        $this->service->processMaturities(CarbonImmutable::now()->addDays(6));

        $this->assertEquals(
            300,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance,
        );
    }

    public function test_maturity_processing_is_idempotent(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(days: 5);
        $investment = $this->service->create($user, $plan, 300);

        $now = CarbonImmutable::now()->addDays(6);

        $this->assertEquals(1, $this->service->processMaturities($now));
        $this->assertEquals(0, $this->service->processMaturities($now));
        $this->assertEquals(
            1000,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL)->balance,
        );
    }

    public function test_principal_wallet_is_locked_while_investment_active(): void
    {
        $user = $this->fundedUser(1000);
        $plan = $this->plan(days: 5);
        $this->service->create($user, $plan, 300);

        $principal = $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL);
        $this->expectException(\App\Domain\Wallets\Exceptions\PrincipalWalletLockedException::class);
        $this->walletService->debit($principal, 10, 'Withdrawal');
    }
}
