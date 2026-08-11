<?php

namespace App\Livewire;

use App\Domain\Wallets\WalletService;
use App\Models\Investment;
use App\Models\Wallet;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.dashboard')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(WalletService $walletService)
    {
        $user = auth()->user();

        $wallets = collect(Wallet::TYPES)
            ->mapWithKeys(fn ($type) => [$type => $walletService->getOrCreate($user, $type)]);

        $totalBalance = $wallets->sum(fn (Wallet $w) => (float) $w->balance);
        $earnings = (float) $wallets->get(Wallet::TYPE_EARNINGS)->balance;
        $referral = (float) $wallets->get(Wallet::TYPE_REFERRAL)->balance;

        $activeInvestments = $user->investments()->where('status', Investment::STATUS_ACTIVE)->count();

        $recentTransactions = $wallets->values()
            ->flatMap(fn (Wallet $w) => $w->transactions()
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn ($tx) => $tx->setAttribute('wallet_type', $w->type)))
            ->sortByDesc('id')
            ->take(6)
            ->values();

        $portfolio = $wallets
            ->map(fn (Wallet $w) => ['type' => $w->type, 'balance' => (float) $w->balance])
            ->values();

        $portfolioConfig = [
            'labels' => $portfolio->pluck('type')->map(fn ($t) => ucfirst($t))->all(),
            'values' => $portfolio->pluck('balance')->map(fn ($b) => round($b, 2))->all(),
        ];

        return view('livewire.dashboard', [
            'wallets' => $wallets,
            'totalBalance' => $totalBalance,
            'activeInvestments' => $activeInvestments,
            'totalEarnings' => $earnings,
            'referralIncome' => $referral,
            'recentTransactions' => $recentTransactions,
            'portfolio' => $portfolio,
            'chart' => $this->buildGrowthCandles($wallets->get(Wallet::TYPE_EARNINGS)),
            'portfolioChart' => $portfolioConfig,
        ]);
    }

    protected function buildGrowthCandles(Wallet $earningsWallet): array
    {
        $transactions = $earningsWallet->transactions()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['created_at', 'type', 'amount']);

        $balance = 0.0;
        $candles = [];
        $dayKey = null;
        $open = $high = $low = $close = 0.0;

        $flush = function () use (&$candles, &$dayKey, &$open, &$high, &$low, &$close) {
            if ($dayKey === null) {
                return;
            }

            // Give each candle visible wicks proportional to the day's move so
            // the OHLC structure reads as candles rather than flat boxes.
            $wick = max(abs($close - $open) * 0.35, 0.05);
            $high = max($open, $close, $high) + $wick;
            $low = max(0.0, min($open, $close, $low) - $wick);

            $candles[] = [
                'x' => \Illuminate\Support\Carbon::parse($dayKey)->startOfDay()->addHours(12)->getTimestampMs(),
                'y' => [round($open, 2), round($high, 2), round($low, 2), round($close, 2)],
            ];
        };

        foreach ($transactions as $tx) {
            $key = $tx->created_at->toDateString();

            if ($key !== $dayKey) {
                $flush();
                $dayKey = $key;
                $open = $high = $low = $close = $balance;
            }

            $balance += $tx->type === 'credit' ? (float) $tx->amount : -(float) $tx->amount;
            $high = max($high, $balance);
            $low = min($low, $balance);
            $close = $balance;
        }

        $flush();

        return [
            'candles' => $candles,
            'swing' => $this->buildSwingSeries($candles),
        ];
    }

    /**
     * Zigzag trend line that alternates high/low swings: it runs up to a
     * swing high (higher high), down to the next swing low (lower low), then
     * repeats the cycle. A swing is only committed when price reverses past
     * the previous extreme by a minimum move, filtering out noise.
     */
    protected function buildSwingSeries(array $candles): array
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

        // Anchor the line to the first close, run it through each swing, and
        // end on the latest close so it spans the whole chart.
        $line = [['x' => $points[0]['x'], 'y' => round($points[0]['c'], 2)]];

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
