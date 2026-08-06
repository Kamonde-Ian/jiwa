<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest', ['title' => 'Forgot Password', 'brand' => 'Reset your password', 'subtitle' => 'We will email you a link to choose a new password.'])] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="auth-note">
        <i class="fa-solid fa-circle-info"></i>
        <span>
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </span>
    </div>

    @if (session('status'))
        <div class="alert alert-success rounded-3 mb-4">{{ __(session('status')) }}</div>
    @endif

    <form wire:submit="sendPasswordResetLink">
        <x-auth.field for="email" :label="__('Email')" type="email" model="email" placeholder="you@example.com" autocomplete="email" :required="true" :autofocus="true" class="mb-4" />

        <button type="submit" class="btn btn-primary w-100 mb-3">
            <i class="fa-solid fa-paper-plane me-2"></i>{{ __('Email Password Reset Link') }}
        </button>

        <p class="text-center small text-muted mb-0">
            <a href="{{ route('login') }}" class="auth-link" wire:navigate><i class="fa-solid fa-arrow-left me-1"></i>{{ __('Back to login') }}</a>
        </p>
    </form>
</div>
