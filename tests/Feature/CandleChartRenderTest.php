<?php

namespace Tests\Feature;

use App\Domain\Wallets\WalletService;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandleChartRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_candlestick_growth_overview(): void
    {
        $user = User::factory()->create(['name' => 'Candle Tester']);

        $svc = app(WalletService::class);
        $wallet = $svc->getOrCreate($user, Wallet::TYPE_EARNINGS);

        $dates = [now()->subDays(4), now()->subDays(3), now()->subDays(2), now()->subDays(1), now()];
        foreach ($dates as $i => $date) {
            $svc->credit($wallet, 10 + $i * 2.5, 'daily earnings');
        }
        // Re-materialise the dates so the candles span multiple days.
        $wallet->transactions()->update(['created_at' => now()->subDays(5)]); // parity fallback
        foreach ($wallet->transactions()->orderBy('id')->get() as $i => $tx) {
            $tx->update(['created_at' => $dates[$i]]);
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('candlestick', false);
        $response->assertSee('upward', false);
        $response->assertSee('Balance', false);
    }
}