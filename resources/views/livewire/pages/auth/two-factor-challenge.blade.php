<?php

use App\Models\User;
use App\Support\TwoFactorAuth;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest', ['title' => 'Two-Factor Authentication', 'brand' => 'Two-factor authentication', 'subtitle' => 'Enter your verification code to complete login.'])] class extends Component
{
    public string $code = '';

    public function confirm(TwoFactorAuth $twoFactorAuth): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('two_factor_pending_user');
        $remember = session('two_factor_pending_remember', false);

        if (! $userId) {
            throw ValidationException::withMessages([
                'code' => __('Your session has expired. Please log in again.'),
            ]);
        }

        $this->ensureIsNotRateLimited();

        $user = User::find($userId);

        if (! $user?->two_factor_enabled || ! $twoFactorAuth->verify($user->google2fa_secret, $this->code)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'code' => __('The provided two-factor code was incorrect.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->forget(['two_factor_pending_user', 'two_factor_pending_remember']);

        Auth::login($user, $remember);

        activity('security')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['action' => 'two_factor_login'])
            ->log('Signed in with two-factor authentication');

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return '2fa|'.(string) session('two_factor_pending_user', 'guest').'|'.request()->ip();
    }
}; ?>

<div>
    <div class="auth-note">
        <i class="fa-solid fa-shield-halved"></i>
        <span>{{ __('Enter the six-digit code from your authenticator app to complete sign in.') }}</span>
    </div>

    <form wire:submit="confirm">
        <x-auth.field for="code" :label="__('Authentication Code')" model="code" placeholder="000000" inputmode="numeric" autocomplete="one-time-code" :required="true" :autofocus="true" :centered="true" :spaced="true" :maxlength="6" class="mb-4" />

        <button type="submit" class="btn btn-primary w-100 mb-3">
            <i class="fa-solid fa-check me-2"></i>{{ __('Verify') }}
        </button>

        <p class="text-center small text-muted mb-0">
            <a href="{{ route('login') }}" class="auth-link" wire:navigate><i class="fa-solid fa-arrow-left me-1"></i>{{ __('Cancel and log in again') }}</a>
        </p>
    </form>
</div>
