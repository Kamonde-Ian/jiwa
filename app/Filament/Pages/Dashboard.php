<?php

namespace App\Filament\Pages;

use App\Models\Deposit;
use App\Models\Investment;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    protected ?string $heading = '';

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getHeroStats(): array
    {
        return [
            ['label' => 'Total Users', 'value' => number_format(User::count())],
            ['label' => 'Active Investments', 'value' => number_format(Investment::where('status', Investment::STATUS_ACTIVE)->count())],
            ['label' => 'Pending Deposits', 'value' => number_format(Deposit::where('status', Deposit::STATUS_PENDING)->count())],
            ['label' => 'Pending Withdrawals', 'value' => number_format(Withdrawal::whereIn('status', [Withdrawal::STATUS_PENDING_REVIEW, Withdrawal::STATUS_APPROVED])->count())],
            ['label' => 'Total Investments', 'value' => '$'.number_format((float) Investment::sum('principal_amount'), 2)],
        ];
    }
}
