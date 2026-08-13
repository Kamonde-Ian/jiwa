<?php

namespace App\Http\Controllers;

use App\Domain\Trading\MarketDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Same-origin proxy for live candlestick data. Lets the trader hub chart fall
 * back to the (cached, server-side) Binance feed when the visitor's browser
 * cannot reach the exchange directly (geo-restricted/CORS-blocked networks).
 */
class MarketKlinesController extends Controller
{
    public function __invoke(Request $request, MarketDataClient $marketData): JsonResponse
    {
        $pair = (string) $request->query('pair', '');
        $interval = (string) $request->query('interval', '5m');
        $limit = min(max((int) $request->query('limit', 250), 1), 1000);

        if (! in_array($pair, $marketData->pairs(), true) || ! $marketData->supportsInterval($interval)) {
            return response()->json(['error' => 'unsupported', 'candles' => []], 400);
        }

        $candles = $marketData->candles($pair, $interval, $limit);

        if (empty($candles)) {
            return response()->json(['error' => 'unavailable', 'candles' => []], 503);
        }

        return response()->json(['candles' => $candles]);
    }
}