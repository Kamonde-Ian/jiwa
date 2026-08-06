<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest', ['title' => 'Log in'])] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $user = $this->form->authenticate();

        if ((bool) $user->two_factor_enabled) {
            session([
                'two_factor_pending_user' => $user->id,
                'two_factor_pending_remember' => $this->form->remember,
            ]);

            session()->regenerate();

            $this->redirect(route('two-factor.challenge'));

            return;
        }

        Auth::login($user, $this->form->remember);

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Autofill the demo account credentials for quick testing.
     */
    public function fillDemo(): void
    {
        $this->form->email = 'user@jiwa.test';
        $this->form->password = 'password';
        $this->form->remember = true;
    }
}; ?>

<div>
    @if (session('status'))
        <div class="alert alert-success rounded-3"><i class="fa-solid fa-circle-check me-2"></i>{{ session('status') }}</div>
    @endif

    <div class="auth-demo">
        <div>
            <div class="auth-demo-title">Demo account</div>
            <div class="auth-demo-credentials">user@jiwa.test · password</div>
        </div>
        <button type="button" wire:click="fillDemo" class="btn btn-primary btn-sm px-3 flex-shrink-0">
            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>{{ __('Autofill') }}
        </button>
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

    <div class="auth-or"><span>{{ __('or use your account') }}</span></div>

    <form wire:submit="login">
        <x-auth.field for="email" :label="__('Email')" type="email" model="form.email" placeholder="you@example.com" autocomplete="username" :required="true" :autofocus="true" />
        <x-auth.field for="password" :label="__('Password')" type="password" model="form.password" placeholder="••••••••" autocomplete="current-password" :required="true" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input wire:model="form.remember" id="remember" type="checkbox" class="form-check-input">
                <label for="remember" class="form-check-label small">{{ __('Remember me') }}</label>
            </div>
            @if (Route::has('password.request'))
                <a class="auth-link small" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">
            <i class="fa-solid fa-right-to-bracket me-2"></i>{{ __('Log in') }}
        </button>

        @if (Route::has('register'))
            <p class="text-center small text-muted mb-0">
                {{ __('New to ').config('app.name').'?' }}
                <a href="{{ route('register') }}" class="auth-link" wire:navigate>{{ __('Create an account') }}</a>
            </p>
        @endif
    </form>
</div>
