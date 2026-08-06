<x-filament-panels::page.simple :heading="null" :subheading="null">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

    <div class="fi-auth" data-auth-card>
        <div class="fi-auth-glow" data-auth-card-glow></div>

        <div class="fi-auth-left">
            <div class="fi-auth-inner">
                <div class="auth-brand">
                    <span class="auth-brand-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 4 8v8l8 6 8-6V8l-8-6zm0 2.5L18 7.6v8.8l-6 4.5-6-4.5V7.6l6-3.1z"/><path d="M11.2 7.8v8.4h1.6V7.8h-1.6zM8.5 9.9l.9 1.3 2.6-1.8V8.1L8.5 9.9z"/></svg>
                    </span>
                    <span class="auth-brand-name">JIWA Admin</span>
                </div>

                <div class="text-center mb-4">
                    <h1 class="auth-heading mb-1">{{ __('Choose a new password') }}</h1>
                    <p class="auth-subtitle">{{ __('Set a strong new password for your account.') }}</p>
                </div>

                <x-filament-panels::form id="form" wire:submit="resetPassword">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>

                <p class="auth-footer text-center mt-4">© {{ date('Y') }} JIWA. All rights reserved.</p>
            </div>
        </div>

        <div class="fi-auth-right" aria-hidden="true">
            <div class="auth-visual">
                <svg class="auth-visual-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2 4 8v8l8 6 8-6V8l-8-6zm0 2.5L18 7.6v8.8l-6 4.5-6-4.5V7.6l6-3.1z"/><path d="M11.2 7.8v8.4h1.6V7.8h-1.6zM8.5 9.9l.9 1.3 2.6-1.8V8.1L8.5 9.9z"/>
                </svg>
                <div class="auth-visual-caption">
                    <strong>Administration console</strong>
                    <span>Manage users, KYC, deposits, investments and withdrawals.</span>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/auth.js') }}"></script>
</x-filament-panels::page.simple>
