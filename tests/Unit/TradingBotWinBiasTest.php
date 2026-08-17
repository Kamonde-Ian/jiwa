<?php

namespace Tests\Unit;

use App\Domain\Trading\MarketDataClient;
use App\Domain\Trading\TradingBotService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeMarketDataClient;
use Tests\TestCase;

class TradingBotWinBiasTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulated_trades_are_more_profitable_than_losing_across_days(): void
    {
        $marketData = new FakeMarketDataClient;
        $this->app->instance(MarketDataClient::class, $marketData);
        $service = app(TradingBotService::class);
        $pool = $service->pool();

        $totalWins = 0;
        $totalLosses = 0;
        $maxResidual = 0.0;

        for ($offset = 1; $offset <= 40; $offset++) {
            $direction = ($offset % 4 === 0) ? 'fall' : 'rise';
            $date = CarbonImmutable::today()->subDays($offset);
            $marketData->seedDay($date->toDateString(), $direction, 40000 + $offset * 100, 2.0 + ($offset % 3));

            $path = $service->sessionPath($pool, $date);

            if ($path['trade_count'] === 0) {
                continue;
            }

            preg_match('/(\d+)W \/ (\d+)L/', $path['strategy'], $m);
            $totalWins += (int) $m[1];
            $totalLosses += (int) $m[2];

            $maxResidual = max($maxResidual, abs(array_sum($path['trades']) - $path['return_pct']));
        }

        $this->assertGreaterThan($totalLosses, $totalWins, 'Profitable trades must outnumber losing ones.');
        $this->assertGreaterThan(0.6, $totalWins / max($totalWins + $totalLosses, 1), 'Win rate should sit comfortably above 60%.');
        $this->assertLessThan(0.0001, $maxResidual, 'Simulated trade returns must still sum exactly to the day net.');
    }
}