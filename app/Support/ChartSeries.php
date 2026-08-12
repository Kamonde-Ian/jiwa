<?php

namespace App\Support;

/**
 * Helpers for building ApexCharts series from OHLC candle data.
 */
class ChartSeries
{
    /**
     * Zigzag trend line that alternates high/low swings: it runs up to a
     * swing high, down to the next swing low, then repeats the cycle. A swing
     * is only committed when price reverses past the previous extreme by a
     * minimum move, filtering out noise.
     *
     * @param  array<int, array{x:int, y:array{0:float,1:float,2:float,3:float}}>  $candles
     * @return array<int, array{x:int, y:float}>
     */
    public static function swingSeries(array $candles): array
    {
        $count = count($candles);

        if ($count === 0) {
            return [];
        }

        $points = array_map(fn ($c) => [
            'x' => $c['x'],
            'h' => $c['y'][1],
            'l' => $c['y'][2],
            'c' => $c['y'][3],
        ], $candles);

        if ($count < 3) {
            return array_map(fn ($p) => ['x' => $p['x'], 'y' => round($p['c'], 2)], $points);
        }

        $span = max(array_column($points, 'h')) - min(array_column($points, 'l'));
        $minMove = max($span * 0.02, 0.01);
        $trendsUp = $points[$count - 1]['c'] >= $points[0]['c'];

        $swings = [];
        $pivot = $points[0];
        $lookingForHigh = $trendsUp;

        for ($i = 1; $i < $count; $i++) {
            $p = $points[$i];

            if ($lookingForHigh) {
                if ($p['h'] > $pivot['h']) {
                    $pivot = $p;
                    continue;
                }

                if ($pivot['h'] - $p['l'] >= $minMove) {
                    $swings[] = ['type' => 'high', 'x' => $pivot['x'], 'y' => $pivot['h']];
                    $pivot = $p;
                    $lookingForHigh = false;
                }

                continue;
            }

            if ($p['l'] < $pivot['l']) {
                $pivot = $p;
                continue;
            }

            if ($p['h'] - $pivot['l'] >= $minMove) {
                $swings[] = ['type' => 'low', 'x' => $pivot['x'], 'y' => $pivot['l']];
                $pivot = $p;
                $lookingForHigh = true;
            }
        }

        // Commit the final extreme so the line always tracks the latest swing.
        $swings[] = [
            'type' => $lookingForHigh ? 'high' : 'low',
            'x' => $pivot['x'],
            'y' => $lookingForHigh ? $pivot['h'] : $pivot['l'],
        ];

        // Anchor the line: if the first swing sits on the first candle, open
        // right on that swing extreme so the cycle starts HH -> LL -> HH;
        // otherwise open on the first close and connect across each swing.
        $line = [];

        if ($swings[0]['x'] === $points[0]['x']) {
            $line[] = ['x' => $swings[0]['x'], 'y' => round($swings[0]['y'], 2)];
        } else {
            $line[] = ['x' => $points[0]['x'], 'y' => round($points[0]['c'], 2)];
        }

        foreach ($swings as $swing) {
            if (end($line)['x'] !== $swing['x']) {
                $line[] = ['x' => $swing['x'], 'y' => round($swing['y'], 2)];
            }
        }

        if (end($line)['x'] !== $points[$count - 1]['x']) {
            $line[] = ['x' => $points[$count - 1]['x'], 'y' => round($points[$count - 1]['c'], 2)];
        }

        return $line;
    }
}