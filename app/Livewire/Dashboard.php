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
     * Zigzag line that moves from wick to wick, tracing the swing structure
     * (higher highs / higher lows alternating with lower highs / lower lows).
     */
    protected function buildSwingSeries(array $candles): array
    {
        $count = count($candles);

        if ($count === 0) {
            return [];
        }

        if ($count < 3) {
            return array_map(fn ($c) => ['x' => $c['x'], 'y' => $c['y'][3]], $candles);
        }

        $points = array_map(fn ($c) => ['x' => $c['x'], 'h' => $c['y'][1], 'l' => $c['y'][2], 'c' => $c['y'][3]], $candles);

        $span = max(array_column($points, 'h')) - min(array_column($points, 'l'));
        $minMove = max($span * 0.05, 0.02);

        // Single-bar-reversal pivots on the wick extremes.
        $pivots = [];
        for ($i = 1; $i < $count - 1; $i++) {
            $prev = $points[$i - 1];
            $curr = $points[$i];
            $next = $points[$i + 1];

            if ($curr['h'] > $prev['h'] && $curr['h'] >= $next['h']) {
                $pivots[] = ['type' => 'high', 'x' => $curr['x'], 'y' => $curr['h']];
            } elseif ($curr['l'] < $prev['l'] && $curr['l'] <= $next['l']) {
                $pivots[] = ['type' => 'low', 'x' => $curr['x'], 'y' => $curr['l']];
            }
        }

        // Zigzag: walk the pivots, extend a same-type pivot when more extreme,
        // and only commit an opposite pivot when the swing is meaningful.
        $legs = [];
        $last = null;

        foreach ($pivots as $pivot) {
            if ($last === null || $pivot['type'] === $last['type']) {
                if ($last === null || $this->isMoreExtreme($pivot, $last)) {
                    if ($last === null) {
                        $legs[] = $pivot;
                    } else {
                        $legs[count($legs) - 1] = $pivot;
                    }
                    $last = $pivot;
                }
                continue;
            }

            if (abs($pivot['y'] - $last['y']) >= $minMove) {
                $legs[] = $pivot;
                $last = $pivot;
            }
        }

        // Anchor the line to the first and last closes so it spans the chart.
        $line = [['x' => $points[0]['x'], 'y' => round($points[0]['c'], 2)]];

        foreach ($legs as $leg) {
            if (end($line)['x'] !== $leg['x']) {
                $line[] = ['x' => $leg['x'], 'y' => round($leg['y'], 2)];
            }
        }

        if (end($line)['x'] !== $points[$count - 1]['x']) {
            $line[] = ['x' => $points[$count - 1]['x'], 'y' => round($points[$count - 1]['c'], 2)];
        }

        return $line;
    }

    protected function isMoreExtreme(array $new, array $old): bool
    {
        if ($new['type'] === 'high') {
            return $new['y'] >= $old['y'];
        }

        return $new['y'] <= $old['y'];
    }
}
