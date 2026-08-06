<?php

namespace App\Livewire;

use App\Domain\Wallets\WalletService;
use App\Models\Wallet;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.dashboard')]
#[Title('My Wallets')]
class Wallets extends Component
{
    public function render(WalletService $walletService)
    {
        $user = auth()->user();

        $wallets = collect(Wallet::TYPES)
            ->mapWithKeys(fn ($type) => [$type => $walletService->getOrCreate($user, $type)]);

        $transactions = $wallets->values()
            ->flatMap(fn (Wallet $wallet) => $wallet->transactions()
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn ($tx) => $tx->setAttribute('wallet_type', $wallet->type)))
            ->sortByDesc('id')
            ->take(20)
            ->values();

        $chart = $this->buildGrowthSeries($wallets->get(Wallet::TYPE_EARNINGS));

        return view('livewire.wallets', [
            'wallets' => $wallets,
            'transactions' => $transactions,
            'chart' => $chart,
        ]);
    }

    protected function buildGrowthSeries(Wallet $earningsWallet): array
    {
        $series = $earningsWallet->transactions()
            ->selectRaw('DATE(created_at) as day, SUM(CASE WHEN type = "credit" THEN amount ELSE -amount END) as net')
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
            'labels' => array_column($points, 0),
            'values' => array_column($points, 1),
        ];
    }
}
