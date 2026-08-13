<?php

namespace Tests\Fakes;

use App\Domain\Trading\MarketDataClient;
use Carbon\CarbonImmutable;

/**
 * Deterministic, in-memory stand-in for MarketDataClient so unit/feature
 * tests never touch the network. Tests seed per-day candle directions to
 * control whether a bot session books a profit or a loss.
 */
class FakeMarketDataClient extends MarketDataClient
{
    protected array $dayOverrides = [];

    protected array $candleOverrides = [];

    public function supportsInterval(string $interval): bool
    {
        return in_array($interval, MarketDataClient::INTERVALS, true);
    }

    /**
     * Seed a full day of candles that drift from $start toward a
     * rise/fall/fiat move of $movePct.
     */
    public function seedDay(string $date, string $direction = 'rise', float $start = 50000.0, float $movePct = 2.0): void
    {
        $factor = match ($direction) {
            'fall' => -1,
            'flat' => 0,
            default => 1,
        };

        $this->dayOverrides[$date] = $this->toCandles($this->series($start, $start * (1 + $factor * $movePct / 100)));
    }

    /**
     * Simulate a day with no market data (network outage / unsupported pair).
     */
    public function clearDay(string $date): void
    {
        $this->dayOverrides[$date] = [];
    }

    /**
     * Seed arbitrary per-timeframe candles for the chart.
     *
     * @param  array<int, float>  $closes
     */
    public function seedCandles(string $pair, string $interval, array $closes): void
    {
        $this->candleOverrides["{$pair}|{$interval}"] = $this->toCandles($closes);
    }

    public function candles(string $pair, string $interval, int $limit = 250): array
    {
        $key = "{$pair}|{$interval}";

        if (isset($this->candleOverrides[$key])) {
            return $this->candleOverrides[$key];
        }

        return $this->toCandles($this->series(50000.0, 50150.0, max($limit, 32)));
    }

    public function dayCandles(string $pair, string $interval, CarbonImmutable $date): array
    {
        return $this->dayOverrides[$date->toDateString()]
            ?? $this->toCandles($this->series(50000.0, 50000.0, 96));
    }

    /**
     * Build candlestick rows progressing forward in time from OHLC closes.
     *
     * @param  array<int, float>  $closes
     * @return array<int, array{x:int, y:array{0:float,1:float,2:float,3:float,4:float}}>
     */
    protected function toCandles(array $closes): array
    {
        $step = 300000;
        $base = CarbonImmutable::now()->utc()->startOfDay()->getTimestampMs() - (count($closes) - 1) * $step;
        $prev = $closes[0] ?? 100.0;
        $out = [];

        foreach ($closes as $i => $close) {
            $close = (float) $close;
            $out[] = [
                'x' => $base + $i * $step,
                'y' => [
                    round($prev, 6),
                    round(max($prev, $close) * 1.0005, 6),
                    round(min($prev, $close) * 0.9995, 6),
                    round($close, 6),
                    1000.0,
                ],
            ];
            $prev = $close;
        }

        return $out;
    }

    /**
     * Deterministic drift between two levels (with a small fixed zigzag so the
     * strategy sees movement without any PRNG noise).
     *
     * @return array<int, float>
     */
    protected function series(float $start, float $end, int $steps = 96): array
    {
        $out = [];

        for ($i = 0; $i <= $steps; $i++) {
            $t = $i / max($steps, 1);
            $zig = sin($i * 1.7) * abs($end - $start) * 0.02;
            $out[] = $start + ($end - $start) * $t + $zig;
        }

        return $out;
    }
}
