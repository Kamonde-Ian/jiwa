<?php

namespace App\Livewire;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
#[Title('Statements')]
class Statements extends Component
{
    use WithPagination;

    public string $walletFilter = 'all';

    public function updatedWalletFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = WalletTransaction::query()
            ->whereHas('wallet', fn ($q) => $q->where('user_id', $user->id))
            ->with('wallet')
            ->latest('id');

        if (in_array($this->walletFilter, Wallet::TYPES, true)) {
            $query->whereHas('wallet', fn ($q) => $q->where('type', $this->walletFilter));
        }

        $transactions = $query->paginate(15);

        $userWalletIds = $user->wallets()->pluck('id');

        $summary = [
            'credits' => (float) WalletTransaction::whereIn('wallet_id', $userWalletIds)->where('type', WalletTransaction::TYPE_CREDIT)->sum('amount'),
            'debits' => (float) WalletTransaction::whereIn('wallet_id', $userWalletIds)->where('type', WalletTransaction::TYPE_DEBIT)->sum('amount'),
        ];

        $cashFlow = $this->buildCashFlow($userWalletIds);

        return view('livewire.statements', [
            'transactions' => $transactions,
            'summary' => $summary,
            'net' => $summary['credits'] - $summary['debits'],
            'walletTypes' => Wallet::TYPES,
            'cashFlow' => $cashFlow,
        ]);
    }

    protected function buildCashFlow($walletIds): array
    {
        $labels = [];
        $credits = [];
        $debits = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $labels[] = $month->format('M Y');

            $credits[] = round(
                (float) WalletTransaction::whereIn('wallet_id', $walletIds)
                    ->where('type', WalletTransaction::TYPE_CREDIT)
                    ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('amount'),
                2,
            );

            $debits[] = round(
                (float) WalletTransaction::whereIn('wallet_id', $walletIds)
                    ->where('type', WalletTransaction::TYPE_DEBIT)
                    ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('amount'),
                2,
            );
        }

        return compact('labels', 'credits', 'debits');
    }
}
