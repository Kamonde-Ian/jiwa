<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest', ['title' => 'Verify Email', 'brand' => 'Verify your email', 'subtitle' => 'Confirm your email address to continue.'])] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="auth-note">
        <i class="fa-solid fa-envelope-circle-check"></i>
        <span>
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </span>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success rounded-3 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="d-grid gap-2">
        <button wire:click="sendVerification" type="button" class="btn btn-primary">
            <i class="fa-solid fa-envelope me-2"></i>{{ __('Resend Verification Email') }}
        </button>

        <button wire:click="logout" type="submit" class="btn btn-outline-secondary">
            <i class="fa-solid fa-right-from-bracket me-2"></i>{{ __('Log Out') }}
        </button>
    </div>
</div>
