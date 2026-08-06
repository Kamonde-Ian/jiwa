<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-bolt" heading="Scheduled Tasks" description="These run automatically every day at 00:10 via the scheduler. Use the buttons below to run them now.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
        </div>
    </x-filament-panels::section>
</x-filament-panels::page>
