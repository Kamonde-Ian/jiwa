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

                <div class="auth-demo">
                    <div>
                        <div class="auth-demo-title">Demo admin account</div>
                        <div class="auth-demo-credentials">admin@jiwa.test · password</div>
                    </div>
                    <x-filament::button size="sm" color="primary" wire:click="fillDemo">
                        Autofill
                    </x-filament::button>
                </div>

                <ul class="auth-socials">
                    <li class="list-none">
                        <a href="#" class="auth-social" aria-label="Instagram">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4zm9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8A1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5a5 5 0 0 1-5 5a5 5 0 0 1-5-5a5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3a3 3 0 0 0 3 3a3 3 0 0 0 3-3a3 3 0 0 0-3-3"/></svg>
                        </a>
                    </li>
                    <li class="list-none">
                        <a href="#" class="auth-social" aria-label="LinkedIn">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M6.94 5a2 2 0 1 1-4-.002a2 2 0 0 1 4 .002M7 8.48H3V21h4zm6.32 0H9.34V21h3.94v-6.57c0-3.66 4.77-4 4.77 0V21H22v-7.93c0-6.17-7.06-5.94-8.72-2.91z"/></svg>
                        </a>
                    </li>
                    <li class="list-none">
                        <a href="#" class="auth-social" aria-label="Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M9.198 21.5h4v-8.01h3.604l.396-3.98h-4V7.5a1 1 0 0 1 1-1h3v-4h-3a5 5 0 0 0-5 5v2.01h-2l-.396 3.98h2.396z"/></svg>
                        </a>
                    </li>
                </ul>

                <div class="auth-or"><span>or use your account</span></div>

                <x-filament-panels::form id="form" wire:submit="authenticate">
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
