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
                    <h1 class="auth-heading mb-1">{{ __('Reset your password') }}</h1>
                    <p class="auth-subtitle">{{ __('We will email you a link to choose a new password.') }}</p>
                </div>

                <div class="auth-note">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; margin-top: 0.125rem; flex-shrink: 0; color: var(--color-border);"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20m1 15h-2v-6h2zm0-8h-2V7h2z"/></svg>
                    <span>
                        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                    </span>
                </div>

                <x-filament-panels::form id="form" wire:submit="request">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>

                <p class="auth-back text-center">
                    <a href="{{ filament()->getLoginUrl() }}" class="auth-link">{{ __('Back to login') }}</a>
                </p>

                <p class="auth-footer text-center mt-3 mb-0">© {{ date('Y') }} JIWA. All rights reserved.</p>
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
