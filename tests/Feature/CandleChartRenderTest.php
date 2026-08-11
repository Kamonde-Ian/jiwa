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
        $response->assertSee('Trend', false);
    }

    public function test_swing_line_alternates_between_highs_and_lows(): void
    {
        $component = new \App\Livewire\Dashboard();
        $method = new \ReflectionMethod(\App\Livewire\Dashboard::class, 'buildSwingSeries');
        $method->setAccessible(true);

        $candles = [
            ['x' => 1000, 'y' => [100, 105, 99, 103]],
            ['x' => 2000, 'y' => [103, 110, 102, 108]], // HH
            ['x' => 3000, 'y' => [108, 109, 98, 101]],  // LL pull-back
            ['x' => 4000, 'y' => [101, 112, 100, 111]], // HH again
            ['x' => 5000, 'y' => [111, 113, 105, 106]],
        ];

        $line = $method->invoke($component, $candles);

        $this->assertGreaterThanOrEqual(4, count($line));

        $ys = array_column($line, 'y');
        $flips = 0;
        for ($i = 2; $i < count($ys); $i++) {
            $prevDir = $ys[$i - 1] <=> $ys[$i - 2];
            $dir = $ys[$i] <=> $ys[$i - 1];
            if ($dir !== 0 && $prevDir !== 0 && $dir !== $prevDir) {
                $flips++;
            }
        }

        // The line must move high -> low -> high (HH -> LL -> HH).
        $this->assertGreaterThanOrEqual(2, $flips);
        $this->assertEquals([103, 110, 98, 113], $ys);
    }
}