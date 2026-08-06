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
            'chart' => $this->buildGrowthSeries($wallets->get(Wallet::TYPE_EARNINGS)),
            'portfolioChart' => $portfolioConfig,
        ]);
    }

    protected function buildGrowthSeries(Wallet $earningsWallet): array
    {
        $series = $earningsWallet->transactions()
            ->selectRaw("DATE(created_at) as day, SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as net")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $balance = 0;
        $points = [];

        foreach ($series as $row) {
            $balance += (float) $row->net;
            $points[] = [$row->day, round($balance, 2)];
        }

        return [
            'labels' => array_map(fn ($p) => \Illuminate\Support\Carbon::parse($p[0])->format('M d'), $points),
            'values' => array_map(fn ($p) => $p[1], $points),
        ];
    }
}
