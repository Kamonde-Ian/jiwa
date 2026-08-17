<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-banknotes" heading="Automated Admin Earnings" description="A configurable commission rate is applied to completed platform transactions (confirmed deposits, completed withdrawals and active investments) and split equally among the admins.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-4 w-4 text-primary-500" />
                    Commission rate
                </div>
                <p class="mt-2 text-2xl font-bold">{{ ($this->rate() * 100) . '%' }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Editable from Settings → Admin Earnings.</p>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <x-filament::icon icon="heroicon-o-users" class="h-4 w-4 text-primary-500" />
                    Admins
                </div>
                <p class="mt-2 text-2xl font-bold">{{ $this->admins()->count() }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Each earning is split equally among them.</p>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 text-success-500" />
                    Automation
                </div>
                <p class="mt-2 text-2xl font-bold">{{ $this->enabled() ? 'Active' : 'Paused' }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Runs daily at 00:30 via the scheduler.</p>
            </div>
        </div>

        <div class="mt-6">
            <x-filament::button color="primary" wire:click="distributeNow" wire:loading.attr="disabled">
                Run distribution now
            </x-filament::button>
        </div>
    </x-filament-panels::section>

    <x-filament::section heading="Admin Balances" description="Current balance of each admin's earnings wallet.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @forelse ($this->admins() as $admin)
                <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $admin->name }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $admin->email }}</p>
                    <p class="mt-3 text-2xl font-bold">${{ number_format($this->adminBalance($admin), 2) }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No admin users found. Assign the <code>admin</code> role to users to begin distributing earnings.</p>
            @endforelse
        </div>
    </x-filament-panels::section>

    <x-filament::section heading="Recent Distributions" description="Latest admin earnings recorded from platform transactions.">
        @if ($this->recentEarnings()->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No distributions yet. Run the distribution now, or wait for the daily scheduled job.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2 font-semibold">Admin</th>
                            <th class="px-3 py-2 font-semibold">Source</th>
                            <th class="px-3 py-2 font-semibold">Rate</th>
                            <th class="px-3 py-2 font-semibold text-right">Amount</th>
                            <th class="px-3 py-2 font-semibold">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->recentEarnings() as $earning)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-200">{{ $earning->admin->name }}</td>
                                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-200">
                                    <span class="inline-flex items-center gap-1">
                                        @switch($earning->source_type)
                                            @case('App\Models\Deposit')
                                                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-3.5 w-3.5 text-success-500" />
                                                Deposit
                                                @break
                                            @case('App\Models\Withdrawal')
                                                <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-3.5 w-3.5 text-danger-500" />
                                                Withdrawal
                                                @break
                                            @default
                                                <x-filament::icon icon="heroicon-o-chart-bar" class="h-3.5 w-3.5 text-primary-500" />
                                                Investment
                                        @endswitch
                                        #{{ $earning->source_id }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-700 dark:text-gray-200">{{ ($earning->rate * 100) . '%' }}</td>
                                <td class="px-3 py-2.5 text-right font-semibold text-gray-700 dark:text-gray-200">${{ number_format($earning->amount, 2) }}</td>
                                <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400">{{ $earning->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament-panels::section>
</x-filament-panels::page>
