<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Platform Wallets') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Balances and gas reserved for transaction fees.') }}
        </x-slot>

        @if ($wallets->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('No platform wallets configured yet.') }}
            </p>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($wallets as $wallet)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $wallet->name }}</h3>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $wallet->type === 'deposit' ? 'bg-success-500/10 text-success-600' : 'bg-danger-500/10 text-danger-600' }}">
                                {{ ucfirst($wallet->type) }}
                            </span>
                        </div>

                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Network') }}</dt>
                                <dd class="text-gray-900 dark:text-white">{{ config("jiwa.networks.{$wallet->network}.name") ?? $wallet->network }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Address') }}</dt>
                                <dd class="truncate font-mono text-xs text-gray-900 dark:text-white">{{ $wallet->address }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Balance') }}</dt>
                                <dd class="text-gray-900 dark:text-white">${{ number_format((float) $wallet->balance, 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Gas (fees)') }}</dt>
                                <dd class="font-semibold text-gray-900 dark:text-white">${{ number_format((float) $wallet->gas_balance, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
