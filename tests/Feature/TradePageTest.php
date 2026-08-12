<?php

namespace Tests\Feature;

use App\Domain\Trading\TradingBotService;
use App\Domain\Wallets\WalletService;
use App\Livewire\Trade;
use App\Models\TradingPool;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TradePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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
}