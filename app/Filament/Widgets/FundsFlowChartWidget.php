<?php

namespace App\Filament\Widgets;

use App\Models\Deposit;
use App\Models\Withdrawal;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FundsFlowChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Funds Flow (last 30 days)';

    protected function getData(): array
    {
        $deposits = Deposit::query()
            ->where('status', Deposit::STATUS_CONFIRMED)
            ->where('confirmed_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(confirmed_at) as day, SUM(amount_usd) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $withdrawals = Withdrawal::query()
            ->where('status', Withdrawal::STATUS_COMPLETED)
            ->where('processed_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(processed_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $inflow = [];
        $outflow = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($day)->format('M j');
            $inflow[] = round((float) ($deposits[$day] ?? 0), 2);
            $outflow[] = round((float) ($withdrawals[$day] ?? 0), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Deposits (USD)',
                    'data' => $inflow,
                    'borderColor' => '#696cff',
                    'backgroundColor' => 'rgba(105, 108, 255, 0.1)',
                ],
                [
                    'label' => 'Withdrawals (USD)',
                    'data' => $outflow,
                    'borderColor' => '#f44336',
                    'backgroundColor' => 'rgba(244, 67, 54, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
