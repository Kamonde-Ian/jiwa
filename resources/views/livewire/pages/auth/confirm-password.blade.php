<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest', ['title' => 'Confirm Password', 'brand' => 'Confirm your password', 'subtitle' => 'This is a secure area of the application.'])] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="auth-note">
        <i class="fa-solid fa-lock"></i>
        <span>{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</span>
    </div>

    <form wire:submit="confirmPassword">
        <x-auth.field for="password" :label="__('Password')" type="password" model="password" placeholder="••••••••" autocomplete="current-password" :required="true" :autofocus="true" class="mb-4" />

        <button type="submit" class="btn btn-primary w-100">
            <i class="fa-solid fa-check me-2"></i>{{ __('Confirm') }}
        </button>
    </form>
</div>
