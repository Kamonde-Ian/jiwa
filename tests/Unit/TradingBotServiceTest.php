<?php

namespace Tests\Unit;

use App\Domain\Trading\TradingBotService;
use App\Domain\Wallets\WalletService;
use App\Models\PoolAllocation;
use App\Models\TradingPool;
use App\Models\TradingSession;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradingBotServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TradingBotService $service;

    protected WalletService $wallets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TradingBotService::class);
        $this->wallets = app(WalletService::class);
    }

    public function test_pool_is_created_lazily_from_config(): void
    {
        $pool = $this->service->pool();

        $this->assertInstanceOf(TradingPool::class, $pool);
        $this->assertSame('JIWA Bot Fund', $pool->name);
        $this->assertSame('J-IWA', $pool->symbol);
        $this->assertEquals(100, (float) $pool->nav);
        $this->assertTrue($pool->is_active);
    }

    public function test_day_path_is_deterministic(): void
    {
        $pool = $this->service->pool();
        $date = CarbonImmutable::parse('2026-08-12');

        $a = $this->service->dayPath($pool, $date);
        $b = $this->service->dayPath($pool, $date);

        $this->assertSame($a, $b);
        $this->assertGreaterThanOrEqual($a['low'], $a['high']);
        $this->assertSame(['open', 'high', 'low', 'close', 'return_pct', 'is_profit', 'pnl', 'trade_count', 'strategy'], array_keys($a));
    }

    public function test_allocate_moves_funds_and_buys_units(): void
    {
        $user = User::factory()->create();
        $this->wallets->credit($this->wallets->getOrCreate($user, Wallet::TYPE_PRINCIPAL), 1000, 'Funding');
        $pool = $this->service->pool();

        $allocation = $this->service->allocate($user, $pool, 500);

        $this->assertEquals(5, $allocation->units);
        $this->assertSame(PoolAllocation::STATUS_ACTIVE, $allocation->status);

        $principal = $this->wallets->getOrCreate($user, Wallet::TYPE_PRINCIPAL);
        $this->assertEquals(500, (float) $principal->balance);
        $this->assertEquals(5, (float) $pool->fresh()->total_units);
    }

    public function test_allocate_requires_sufficient_balance(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient');

        $user = User::factory()->create();
        $this->service->allocate($user, $this->service->pool(), 500);
    }

    public function test_withdraw_releases_funds_to_earnings_wallet(): void
    {
        $user = User::factory()->create();
        $this->wallets->credit($this->wallets->getOrCreate($user, Wallet::TYPE_PRINCIPAL), 1000, 'Funding');
        $pool = $this->service->pool();
        $allocation = $this->service->allocate($user, $pool, 500);

        $this->service->withdraw($user, $pool, 200);

        $this->assertEquals(200 + 0.0, (float) $this->wallets->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance);

        $allocation->refresh();
        $this->assertEquals(3, $allocation->units);

        $this->service->withdraw($user, $pool, $allocation->currentValue());
        $this->assertSame(PoolAllocation::STATUS_CLOSED, $allocation->fresh()->status);
    }

    public function test_daily_cycle_is_idempotent_and_moves_nav(): void
    {
        $user = User::factory()->create();
        $this->wallets->credit($this->wallets->getOrCreate($user, Wallet::TYPE_PRINCIPAL), 1000, 'Funding');
        $pool = $this->service->pool();
        $this->service->allocate($user, $pool, 1000);

        $day = CarbonImmutable::today()->subDays(3);

        $first = $this->service->runDailyCycle($day);
        $navAfter = (float) $pool->fresh()->nav;
        $this->assertSame(1, $first['settled']);

        $second = $this->service->runDailyCycle($day);
        $this->assertSame(0, $second['settled']);
        $this->assertEquals($navAfter, (float) $pool->fresh()->nav);

        $this->assertSame(1, TradingSession::where('session_date', $day->toDateString())->count());
    }

    public function test_profit_days_credit_returns_and_loss_days_are_absorbed(): void
    {
        $user = User::factory()->create();
        $this->wallets->credit($this->wallets->getOrCreate($user, Wallet::TYPE_PRINCIPAL), 2000, 'Funding');
        $pool = $this->service->pool();
        $allocation = $this->service->allocate($user, $pool, 1000);

        // Find deterministic profit and loss days within a window.
        $profitDay = $lossDay = null;

        for ($offset = 40; $offset >= 1; $offset--) {
            $date = CarbonImmutable::today()->subDays($offset);
            $path = $this->service->dayPath($pool, $date);

            if ($path['is_profit'] && $profitDay === null) {
                $profitDay = $date;
            }
            if (! $path['is_profit'] && $lossDay === null) {
                $lossDay = $date;
            }
        }

        $this->assertNotNull($profitDay, 'Expected at least one deterministic profit day in window');
        $this->assertNotNull($lossDay, 'Expected at least one deterministic loss day in window');

        // Loss day first: no payout, position value shrinks.
        $this->service->runDailyCycle($lossDay);
        $pool->refresh();

        $this->assertSame(
            0,
            $this->wallets->getOrCreate($user, Wallet::TYPE_EARNINGS)
                ->transactions()->where('type', 'credit')->count(),
            'Loss days must not credit any payout.'
        );

        $valueAfterLoss = $allocation->fresh()->currentValue((float) $pool->nav);
        $this->assertLessThan(1000, $valueAfterLoss);

        // Profit day: a payout is credited to the earnings wallet.
        $this->service->runDailyCycle($profitDay);
        $pool->refresh();

        $credited = (float) $this->wallets->getOrCreate($user, Wallet::TYPE_EARNINGS)
            ->transactions()->where('type', 'credit')->sum('amount');

        $this->assertGreaterThan(0, $credited, 'Profit days must credit a payout to the earnings wallet.');
        $this->assertSame(PoolAllocation::STATUS_ACTIVE, $allocation->fresh()->status);
    }

    public function test_run_daily_cycle_across_several_days_pays_out_gains(): void
    {
        $user = User::factory()->create();
        $this->wallets->credit($this->wallets->getOrCreate($user, Wallet::TYPE_PRINCIPAL), 5000, 'Funding');
        $pool = $this->service->pool();

        $this->service->allocate($user, $pool, 2000);

        $expected = 0;

        for ($offset = 30; $offset >= 1; $offset--) {
            $date = CarbonImmutable::today()->subDays($offset);
            $result = $this->service->runDailyCycle($date);
            $this->assertSame(1, $result['settled']);
            $expected += $result['paid'];
        }

        $payouts = $this->wallets->getOrCreate($user, Wallet::TYPE_EARNINGS)
            ->transactions()
            ->where('type', 'credit')
            ->where('description', 'like', 'Bot trading result%')
            ->count();

        $this->assertSame($expected, $payouts);
        $this->assertGreaterThan(0, $payouts, 'The 30-day window should contain at least one profit day.');
    }
}