<?php

namespace App\Livewire;

use App\Domain\Referrals\ReferralService;
use App\Domain\Wallets\WalletService;
use App\Models\Wallet;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
#[Title('Referrals')]
class Referrals extends Component
{
    use WithPagination;

    public function render(ReferralService $referralService, WalletService $walletService)
    {
        $user = auth()->user();

        $chart = $this->buildCommissionChart($user);

        return view('livewire.referrals', [
            'referralLink' => $referralService->referralLink($user),
            'qualified' => $referralService->isQualified($user),
            'qualificationMinimum' => (float) config('jiwa.referral_qualification_minimum'),
            'commissionRate' => (float) config('jiwa.referral_commission_rate'),
            'referralBalance' => (float) $walletService->getOrCreate($user, Wallet::TYPE_REFERRAL)->balance,
            'earnings' => $user->referralEarnings()->with('referredUser', 'investment')->latest('id')->paginate(8),
            'commissionChart' => $chart,
            'stats' => [
                'balance' => (float) $walletService->getOrCreate($user, Wallet::TYPE_REFERRAL)->balance,
                'earned' => (float) $user->referralEarnings()->sum('amount'),
                'count' => $user->referrals()->count(),
            ],
        ]);
    }

    protected function buildCommissionChart($user): array
    {
        $labels = [];
        $values = [];

        $rows = $user->referralEarnings()
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get(['created_at', 'amount'])
            ->reduce(function (array $totals, $tx) {
                $month = $tx->created_at->format('Y-m');
                $totals[$month] = ($totals[$month] ?? 0) + $tx->amount;

                return $totals;
            }, []);

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $labels[] = $month->format('M Y');
            $values[] = round((float) ($rows[$month->format('Y-m')] ?? 0), 2);
        }

        return compact('labels', 'values');
    }
}
