<?php

namespace Tests\Feature;

use App\Domain\Trading\MarketDataClient;
use App\Domain\Trading\TradingBotService;
use App\Domain\Wallets\WalletService;
use App\Livewire\Trade;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Fakes\FakeMarketDataClient;
use Tests\TestCase;

class TradePageTest extends TestCase
{
    use RefreshDatabase;

    protected FakeMarketDataClient $marketData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marketData = new FakeMarketDataClient;
        $this->app->instance(MarketDataClient::class, $this->marketData);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_trade_page_renders_the_bot_fund_hub(): void
    {
        $pool = app(TradingBotService::class)->pool();

        $this->get(route('trade'))
            ->assertOk()
            ->assertSee($pool->name)
            ->assertSee('Allocate to Bot Fund')
            ->assertSee('Daily Bot Results');
    }

    public function test_trade_page_disables_when_trading_off(): void
    {
        config()->set('jiwa.trading_enabled', false);

        $this->get(route('trade'))->assertNotFound();
    }

    public function test_trade_page_charts_live_market_data_with_pair_and_timeframe_selector(): void
    {
        app(TradingBotService::class)->pool();

        $this->get(route('trade'))
            ->assertOk()
            ->assertSee('BTC/USDT')
            ->assertSee('ETH/USDT')
            ->assertSee('BNB/USDT')
            ->assertSee('Binance')
            ->assertSee('5m')
            ->assertSee('15m')
            ->assertSee('1h')
            ->assertSee('1d')
            ->assertDontSee('Synthetic');
    }

    public function test_user_can_switch_pair_and_timeframe_via_livewire(): void
    {
        app(TradingBotService::class)->pool();

        Livewire::test(Trade::class)
            ->assertSet('pair', 'BTC/USDT')
            ->assertSet('timeframe', '5m')
            ->call('setPair', 'ETH/USDT')
            ->assertSet('pair', 'ETH/USDT')
            ->call('setPair', 'BTC')
            ->assertSet('pair', 'ETH/USDT')
            ->call('setTimeframe', '1h')
            ->assertSet('timeframe', '1h')
            ->call('setTimeframe', '3m')
            ->assertSet('timeframe', '1h');
    }

    public function test_chart_only_exposes_supported_pairs_with_5m_as_lowest_timeframe(): void
    {
        $pairs = collect(config('jiwa.trading_pairs'))->pluck('symbol')->all();
        $this->assertSame(['BTC/USDT', 'ETH/USDT', 'BNB/USDT'], $pairs);

        $timeframes = config('jiwa.trading_timeframes');
        $this->assertSame('5m', $timeframes[0]);
    }

    public function test_user_can_allocate_via_livewire(): void
    {
        $wallets = app(WalletService::class);
        $wallets->credit($wallets->getOrCreate($this->user, Wallet::TYPE_PRINCIPAL), 1000, 'Funding');

        $pool = app(TradingBotService::class)->pool();
        $navBefore = (float) $pool->nav;

        Livewire::test(Trade::class)
            ->set('allocateAmount', 400)
            ->call('allocate')
            ->assertHasNoErrors()
            ->assertSet('allocateAmount', null);

        $this->assertDatabaseHas('pool_allocations', [
            'user_id' => $this->user->id,
            'pool_id' => $pool->id,
            'status' => 'active',
        ]);

        $this->assertEquals(400 / $navBefore, floatval($this->user->poolAllocations()->first()->units));
        $this->assertEquals(600, (float) $wallets->getOrCreate($this->user, Wallet::TYPE_PRINCIPAL)->fresh()->balance);
    }

    public function test_user_can_withdraw_via_livewire(): void
    {
        $wallets = app(WalletService::class);
        $wallets->credit($wallets->getOrCreate($this->user, Wallet::TYPE_PRINCIPAL), 1000, 'Funding');

        $pool = app(TradingBotService::class)->pool();
        app(TradingBotService::class)->allocate($this->user, $pool, 500);

        Livewire::test(Trade::class)
            ->set('withdrawAmount', 200)
            ->call('withdraw')
            ->assertHasNoErrors()
            ->assertSet('withdrawAmount', null);

        $this->assertEquals(3, (float) $this->user->poolAllocations()->first()->fresh()->units);
        $this->assertEquals(200, (float) $wallets->getOrCreate($this->user, Wallet::TYPE_EARNINGS)->fresh()->balance);
    }

    public function test_available_principal_counts_bot_fund_margin_after_full_allocation(): void
    {
        $pool = app(TradingBotService::class)->pool();

        $wallets = app(WalletService::class);
        $wallets->credit($wallets->getOrCreate($this->user, Wallet::TYPE_PRINCIPAL), 1000, 'Funding');

        Livewire::test(Trade::class)
            ->set('allocateAmount', 1000)
            ->call('allocate')
            ->assertHasNoErrors();

        // Balance left over in the principal wallet is now zero…
        $this->assertEquals(0, (float) $wallets->getOrCreate($this->user, Wallet::TYPE_PRINCIPAL)->fresh()->balance);

        // …but the funds are at work in the pool as margin, so the stat
        // must still show them as available principal.
        $service = app(TradingBotService::class);
        $positionValue = $this->user->poolAllocations()
            ->where('status', 'active')
            ->get()
            ->reduce(fn ($c, $a) => $c + $a->currentValue((float) $service->pool()->nav), 0.0);

        $this->assertGreaterThan(0, $positionValue);

        $html = Livewire::test(Trade::class)->html();

        $this->assertStringContainsString(
            'Available principal',
            $html
        );
        $this->assertStringContainsString(
            '$' . number_format($positionValue, 2),
            $html
        );
    }

    public function test_allocation_above_principal_is_rejected(): void
    {
        $wallets = app(WalletService::class);
        $wallets->credit($wallets->getOrCreate($this->user, Wallet::TYPE_PRINCIPAL), 200, 'Funding');

        app(TradingBotService::class)->pool();

        Livewire::test(Trade::class)
            ->set('allocateAmount', 999)
            ->call('allocate')
            ->assertHasErrors('allocateAmount');

        $this->assertDatabaseCount('pool_allocations', 0);
    }

    public function test_defaults_are_present_without_any_activity(): void
    {
        $pool = app(TradingBotService::class)->pool();

        $this->get(route('trade'))
            ->assertOk()
            ->assertSee('$'.number_format($pool->min_allocate, 0));
    }

    public function test_market_klines_proxy_returns_candles_for_supported_pairs(): void
    {
        $this->marketData->seedCandles('BTC/USDT', '5m', [50000, 50100, 50200]);

        $this->getJson(route('trade.market.klines', ['pair' => 'BTC/USDT', 'interval' => '5m', 'limit' => 3]))
            ->assertOk()
            ->assertJsonCount(3, 'candles')
            ->assertJsonPath('candles.0.x', fn ($x) => is_int($x))
            ->assertJsonPath('candles.0.y.3', fn ($close) => abs($close - 50000) < 1);
    }

    public function test_market_klines_proxy_rejects_unknown_pairs(): void
    {
        $this->getJson(route('trade.market.klines', ['pair' => 'DOGE/USDT', 'interval' => '5m']))
            ->assertStatus(400);
    }

    public function test_market_klines_proxy_reports_unavailable_when_market_empty(): void
    {
        $this->marketData->seedCandles('BTC/USDT', '5m', []);

        $this->getJson(route('trade.market.klines', ['pair' => 'BTC/USDT', 'interval' => '5m']))
            ->assertStatus(503);
    }

    public function test_user_can_stop_and_restart_the_bot_from_the_trade_page(): void
    {
        app(TradingBotService::class)->pool();

        $this->get(route('trade'))
            ->assertOk()
            ->assertSee('Stop bot')
            ->assertDontSee('Start bot');

        Livewire::test(Trade::class)
            ->call('stopBot')
            ->assertSet('pair', 'BTC/USDT');

        $this->assertFalse(app(TradingBotService::class)->pool()->fresh()->is_running);

        $this->get(route('trade'))
            ->assertOk()
            ->assertSee('Start bot')
            ->assertSee('Bot paused');

        Livewire::test(Trade::class)
            ->call('startBot');

        $this->assertTrue(app(TradingBotService::class)->pool()->fresh()->is_running);
    }

    public function test_trade_page_still_renderable_with_allocations_and_preview_when_bot_stopped(): void
    {
        $pool = app(TradingBotService::class)->pool();
        $pool->update(['is_running' => false]);

        $this->get(route('trade'))
            ->assertOk()
            ->assertSee($pool->name)
            ->assertSee('Daily Bot Results')
            ->assertSee('Start bot');
    }

    public function test_today_live_row_shows_live_pool_pnl_and_trade_chips_when_live(): void
    {
        $wallets = app(WalletService::class);
        $wallets->credit($wallets->getOrCreate($this->user, Wallet::TYPE_PRINCIPAL), 1000, 'Funding');

        $service = app(TradingBotService::class);
        $pool = $service->pool();
        $service->allocate($this->user, $pool, 1000);

        $this->marketData->seedDay(\Carbon\CarbonImmutable::today()->toDateString(), 'rise', 50000, 2.5);

        $html = Livewire::test(Trade::class)->html();

        $this->assertStringContainsString('Today', $html);
        $this->assertStringContainsString('LIVE', $html);
        $this->assertStringContainsString('live est.', $html);
        $this->assertStringContainsString('Live trades:', $html);
        $this->assertStringNotContainsString('pending', $html);
    }
}
