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
            'swing' => \App\Support\ChartSeries::swingSeries($candles),
        ];
    }
}
