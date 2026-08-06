<?php

namespace App\Filament\Widgets;

use App\Models\Deposit;
use App\Models\Investment;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $aum = (float) Investment::where('status', Investment::STATUS_ACTIVE)->sum('principal_amount');

        return [
            Stat::make('Total Users', number_format(User::count()))
                ->description('Registered accounts')
                ->icon('heroicon-o-users'),

            Stat::make('Active Investments', number_format(Investment::where('status', Investment::STATUS_ACTIVE)->count()))
                ->description('Assets under management: $'.number_format($aum, 2))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Pending Deposits', number_format(Deposit::where('status', Deposit::STATUS_PENDING)->count()))
                ->description('Awaiting confirmation')
                ->icon('heroicon-o-arrow-down-circle')
                ->color('warning'),

            Stat::make('Pending Withdrawals', number_format(Withdrawal::whereIn('status', [Withdrawal::STATUS_PENDING_REVIEW, Withdrawal::STATUS_APPROVED])->count()))
                ->description('Awaiting processing')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('info'),
        ];
    }
}
