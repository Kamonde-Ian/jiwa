<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-bolt" heading="Scheduled Tasks" description="These run automatically every day at 00:10 via the scheduler. Use the buttons below to run them now.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <x-filament::icon icon="heroicon-o-banknotes" class="h-4 w-4 text-primary-500" />
                    Credit daily interest
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Credits interest to every eligible active investment once per configured interval
                    (<code>jiwa.interest_credit_hours</code>). Idempotent — safe to run repeatedly.
                </p>
                <div class="mt-4">
                    <x-filament::button color="primary" wire:click="creditInterest" wire:loading.attr="disabled">
                        Credit interest now
                    </x-filament::button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <x-filament::icon icon="heroicon-o-lock-closed" class="h-4 w-4 text-primary-500" />
                    Process maturities
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Releases principal for every investment past its maturity date, routing it to the configured
                    destination (<code>jiwa.matured_principal_destination</code>).
                </p>
                <div class="mt-4">
                    <x-filament::button color="primary" wire:click="processMaturities" wire:loading.attr="disabled">
                        Process maturities now
                    </x-filament::button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <x-filament::icon icon="heroicon-o-play" class="h-4 w-4 text-primary-500" />
                    Run both
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Runs the full daily cycle — credit interest, then process maturities — exactly like the
                    scheduled 00:10 job.
                </p>
                <div class="mt-4">
                    <x-filament::button color="primary" wire:click="runAll" wire:loading.attr="disabled">
                        Run full cycle
                    </x-filament::button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <span class="relative flex h-2.5 w-2.5">
                        <span @class([
                            'absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping',
                            'bg-success-400' => $this->botPool()->is_running,
                            'bg-gray-400' => ! $this->botPool()->is_running,
                        ])></span>
                        <span @class([
                            'relative inline-flex rounded-full h-2.5 w-2.5',
                            'bg-success-500' => $this->botPool()->is_running,
                            'bg-gray-400' => ! $this->botPool()->is_running,
                        ])></span>
                    </span>
                    {{ $this->botPool()->is_running ? 'Bot running' : 'Bot stopped' }}
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Settles the daily bot trading cycle on live market data at 00:20. Start/stop controls whether
                    new sessions are booked — stopping it does <strong>not</strong> interrupt existing
                    allocations or withdrawals.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-filament::button color="primary" wire:click="runBot" wire:loading.attr="disabled">
                        Run bot cycle now
                    </x-filament::button>
                    @if ($this->botPool()->is_running)
                        <x-filament::button color="danger" wire:click="stopBot" wire:loading.attr="disabled">
                            Stop bot
                        </x-filament::button>
                    @else
                        <x-filament::button color="success" wire:click="startBot" wire:loading.attr="disabled">
                            Start bot
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </div>
    </x-filament-panels::section>
</x-filament-panels::page>
