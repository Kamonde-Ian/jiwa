<?php

namespace App\Domain\Trading;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live market data provider backed by the free Binance public REST API
 * (no API key required). Exposes candlestick data for the platform's major
 * currency pairs at timeframes down to 5 minutes, plus OHLCTick summaries.
 *
 * All responses are cached with a short TTL so the Trade page and the daily
 * bot cycle stay fast and do not hammer the exchange on every render.
 */
class MarketDataClient
{
    /**
     * Supported kline intervals, 5 minutes being the shortest.
     */
    public const INTERVALS = ['5m', '15m', '30m', '1h', '4h', '1d'];

    /**
     * Platform pairs -> Binance symbol mapping. Only the major pairs the
     * platform supports are exposed; everything else is rejected.
     */
    protected array $symbolMap = [
        'BTC/USDT' => 'BTCUSDT',
        'ETH/USDT' => 'ETHUSDT',
        'BNB/USDT' => 'BNBUSDT',
    ];

    /**
     * Binance public endpoints. api.binance.com may occasionally be
     * geo-restricted, so fall back across the mirror hosts.
     */
    protected array $hosts = [
        'https://api.binance.com',
        'https://api1.binance.com',
        'https://api2.binance.com',
    ];

    public function binanceSymbol(string $pair): string
    {
        return $this->symbolMap[$pair] ?? strtoupper(str_replace('/', '', $pair));
    }

    public function supportsInterval(string $interval): bool
    {
        return in_array($interval, self::INTERVALS, true);
    }

    /**
     * The major pairs the platform supports for charting and trading.
     *
     * @return string[]
     */
    public function pairs(): array
    {
        return array_keys($this->symbolMap);
    }

    /**
     * The most recent candles for a pair/interval.
     *
     * @return array<int, array{x:int, y:array{0:float,1:float,2:float,3:float,4:float}}>
     */
    public function candles(string $pair, string $interval, int $limit = 250): array
    {
        if (! isset($this->symbolMap[$pair]) || ! $this->supportsInterval($interval) || $limit <= 0) {
            return [];
        }

        $key = 'market.candles.'.str_replace('/', '', $pair).".{$interval}.{$limit}";

        return Cache::flexible($key, [5, 45], function () use ($pair, $interval, $limit) {
            $rows = $this->getKlines([
                'symbol' => $this->binanceSymbol($pair),
                'interval' => $interval,
                'limit' => $limit,
            ]);

            if (empty($rows)) {
                Log::warning('Market candles unavailable.', ['pair' => $pair, 'interval' => $interval]);

                return [];
            }

            return $this->normalize($rows);
        });
    }

    /**
     * Candles covering a single UTC calendar day (used by the pool bot engine
     * so the daily settlement is driven by real, immutable market data).
     *
     * @return array<int, array{x:int, y:array{0:float,1:float,2:float,3:float,4:float}}>
     */
    public function dayCandles(string $pair, string $interval, CarbonImmutable $date): array
    {
        if (! isset($this->symbolMap[$pair]) || ! $this->supportsInterval($interval)) {
            return [];
        }

        $start = CarbonImmutable::parse($date->toDateString(), 'UTC')->startOfDay();
        $end = $start->addDay();

        $key = 'market.day.'.str_replace('/', '', $pair).".{$interval}.{$date->toDateString()}";

        return Cache::flexible($key, [60, 300], function () use ($pair, $interval, $start, $end) {
            $rows = $this->getKlines([
                'symbol' => $this->binanceSymbol($pair),
                'interval' => $interval,
                'startTime' => $start->getTimestampMs(),
                'endTime' => $end->getTimestampMs(),
            ]);

            if (empty($rows)) {
                Log::warning('Market day candles unavailable.', ['pair' => $pair, 'interval' => $interval]);

                return [];
            }

            return $this->normalize($rows);
        });
    }

    /**
     * Flatten a Binance kline row into the ApexCharts candlestick shape used
     * across the app: x = open-time ms, y = [open, high, low, close, volume].
     *
     * @return array<int, array{x:int, y:array{0:float,1:float,2:float,3:float,4:float}}>
     */
    protected function normalize(array $rows): array
    {
        return collect($rows)->map(fn (array $k) => [
            'x' => (int) $k[0],
            'y' => [
                (float) $k[1],
                (float) $k[2],
                (float) $k[3],
                (float) $k[4],
                (float) $k[5],
            ],
        ])->values()->all();
    }

    protected function getKlines(array $params): array
    {
        foreach ($this->hosts as $host) {
            try {
                // Public, read-only endpoint (no auth/secrets travel here), so
                // peer verification is relaxed for hosts whose CA bundle is
                // not configured (common on Windows PHP builds).
                $response = Http::withoutVerifying()
                    ->timeout(6)
                    ->get($host.'/api/v3/klines', $params);

                if ($response->successful()) {
                    return $response->json() ?: [];
                }
            } catch (\Throwable $e) {
                // Try the next mirror host.
            }
        }

        return [];
    }

    /**
     * Summarize a candle window into a compact ticker: latest price plus the
     * day/window open, high, low and change. Pure and deterministic.
     *
     * @param  array<int, array{x:int, y:array{0:float,1:float,2:float,3:float,4:float}}>  $candles
     * @return array{price:float, open:float, high:float, low:float, change_pct:float, is_profit:bool, live:bool, count:int}
     */
    public static function summarize(array $candles): array
    {
        if (empty($candles)) {
            return [
                'price' => 0.0,
                'open' => 0.0,
                'high' => 0.0,
                'low' => 0.0,
                'change_pct' => 0.0,
                'is_profit' => false,
                'live' => false,
                'count' => 0,
            ];
        }

        $open = (float) $candles[0]['y'][0];
        $high = null;
        $low = null;
        $close = 0.0;

        foreach ($candles as $candle) {
            $high = $high === null ? (float) $candle['y'][1] : max($high, (float) $candle['y'][1]);
            $low = $low === null ? (float) $candle['y'][2] : min($low, (float) $candle['y'][2]);
            $close = (float) $candle['y'][3];
        }

        $changePct = $open > 0 ? (($close - $open) / $open) * 100 : 0.0;

        return [
            'price' => $close,
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'change_pct' => round($changePct, 4),
            'is_profit' => $changePct >= 0,
            'live' => true,
            'count' => count($candles),
        ];
    }
}
