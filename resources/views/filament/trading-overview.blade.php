<div class="space-y-4">
    @php
        $service = app(\App\Domain\Trading\TradingBotService::class);
        $wallets = app(\App\Domain\Wallets\WalletService::class);
        $pool = $service->pool();
        $nav = (float) $pool->nav;
        $botRunning = (bool) $pool->is_running;

        $allocations = $user->poolAllocations()
            ->where('status', \App\Models\PoolAllocation::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        $positionValue = $allocations->reduce(fn ($c, $a) => $c + $a->currentValue($nav), 0.0);
        $units = $allocations->sum('units');

        $earnings = $wallets->getOrCreate($user, \App\Models\Wallet::TYPE_EARNINGS);
        $principal = $wallets->getOrCreate($user, \App\Models\Wallet::TYPE_PRINCIPAL);

        $invested = (float) $principal->transactions()
            ->where('type', 'debit')
            ->where('description', 'like', 'Allocated to%')
            ->sum('amount');

        $withdrawnFromPool = (float) $earnings->transactions()
            ->where('type', 'credit')
            ->where('description', 'like', 'Bot fund withdrawal%')
            ->sum('amount');

        $returnsCredited = (float) $earnings->transactions()
            ->where('type', 'credit')
            ->where('description', 'like', 'Bot trading result%')
            ->sum('amount');

        $netInPool = $invested - $withdrawnFromPool;
        $unrealized = $positionValue - $netInPool;
        $totalReturn = $returnsCredited + $unrealized;
        $returnPct = $invested > 0 ? ($totalReturn / $invested) * 100 : 0.0;

        $today = $service->today($pool);
        $todayPnl = $positionValue * ($today['change_pct'] / 100);
        $todayPoolPnl = (float) $pool->total_units > 0
            ? ($today['price'] - $today['open']) * (float) $pool->total_units
            : 0.0;

        $fmt = fn ($n) => '$'.number_format((float) $n, 2);
        $sign = fn ($n) => $n >= 0 ? '+' : '-';
    @endphp

    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
            <span @class([
                'relative flex h-2.5 w-2.5',
                'text-success-500' => $botRunning,
                'text-gray-400' => ! $botRunning,
            ])>
                <span @class([
                    'absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping',
                    'bg-success-400' => $botRunning,
                    'bg-gray-400' => ! $botRunning,
                ])></span>
                <span @class([
                    'relative inline-flex rounded-full h-2.5 w-2.5',
                    'bg-success-500' => $botRunning,
                    'bg-gray-400' => ! $botRunning,
                ])></span>
            </span>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ $botRunning ? 'Bot running' : 'Bot stopped' }}
            </span>
        </div>
        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ $today['live'] ? 'Today live · ' : 'Today settled · ' }}
            {{ $fmt($today['open']) }} → {{ $fmt($today['price']) }}
            <span @class(['font-medium', 'text-success-600 dark:text-success-400' => $today['is_profit'], 'text-danger-600 dark:text-danger-400' => ! $today['is_profit']])>
                {{ $sign($today['change_pct']) }}{{ number_format(abs($today['change_pct']), 2) }}%
            </span>
            · {{ $today['trades'] }} trade{{ $today['trades'] === 1 ? '' : 's' }}
        </span>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Position value</div>
            <div class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $fmt($positionValue) }}</div>
            <div class="text-xs text-gray-400">{{ number_format($units, 6) }} units · NAV {{ $fmt($nav) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Today's result</div>
            <div @class(['mt-1 text-xl font-bold', $todayPnl >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'])>
                {{ $sign($todayPnl) }}{{ $fmt(abs($todayPnl)) }}
            </div>
            <div class="text-xs text-gray-400">{{ $sign($today['change_pct']) }}{{ number_format(abs($today['change_pct']), 2) }}% on position</div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Returns credited</div>
            <div class="mt-1 text-xl font-bold text-success-600 dark:text-success-400">{{ $fmt($returnsCredited) }}</div>
            <div class="text-xs text-gray-400">swept to Earnings Wallet</div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Total return (incl. unrealised)</div>
            <div @class(['mt-1 text-xl font-bold', $totalReturn >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'])>
                {{ $sign($totalReturn) }}{{ $fmt(abs($totalReturn)) }}
            </div>
            <div class="text-xs text-gray-400">{{ $sign($returnPct) }}{{ number_format(abs($returnPct), 2) }}% on ${{ number_format($invested, 2) }} deployed</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Net cash deployed</div>
            <div class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $fmt(max($netInPool, 0)) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Pool P&L (live est.)</div>
            <div @class(['mt-1 text-base font-semibold', $todayPoolPnl >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'])>
                {{ $sign($todayPoolPnl) }}{{ $fmt(abs($todayPoolPnl)) }}
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Unrealised</div>
            <div @class(['mt-1 text-base font-semibold', $unrealized >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'])>
                {{ $sign($unrealized) }}{{ $fmt(abs($unrealized)) }}
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Withdrawn from fund</div>
            <div class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $fmt($withdrawnFromPool) }}</div>
        </div>
    </div>

    @if (! empty($today['trade_returns']))
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <div class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                Today's live trades · {{ $today['strategy'] }}
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($today['trade_returns'] as $i => $tr)
                    <span @class([
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                        'bg-success-100 text-success-700 dark:bg-success-400/10 dark:text-success-400' => $tr >= 0,
                        'bg-danger-100 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400' => $tr < 0,
                    ])>
                        {{ $sign($tr) }}{{ number_format(abs($tr), 2) }}%
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
