<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest', ['title' => 'Register', 'brand' => 'Create your account', 'subtitle' => 'Join thousands of investors earning daily returns.'])] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $ref = null;
    public int $step = 1;

    public function mount(): void
    {
        $this->ref = request()->query('ref');
    }

    /**
     * Validate the current wizard step, then advance to the next one.
     */
    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            ]);
        }

        if ($this->step === 2) {
            $this->validate([
                'password' => ['required', 'string', 'confirmed', 'max:255', new \App\Rules\StrongPassword()],
            ]);
        }

        $this->step = min(3, $this->step + 1);
    }

    /**
     * Go back to the previous wizard step.
     */
    public function previousStep(): void
    {
        $this->resetValidation();
        $this->step = max(1, $this->step - 1);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', 'max:255', new \App\Rules\StrongPassword()],
            'ref' => ['required', 'string', 'max:255'],
        ]);

        $referrer = User::where('referral_code', $this->ref)->first();
        if (! $referrer) {
            $this->addError('ref', __('Invalid referral code.'));

            return;
        }

        $rawPassword = $validated['password'];
        $validated['password'] = Hash::make($rawPassword);
        $validated['password_plain'] = $rawPassword;
        $validated['referral_code'] = User::generateReferralCode();
        $validated['referred_by'] = $referrer?->id;

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <ol class="auth-wizard-steps">
            <li class="auth-wizard-step {{ $step === 1 ? 'active' : '' }} {{ $step > 1 ? 'done' : '' }}">
                <span class="auth-wizard-dot">1</span>
                <span class="auth-wizard-label">{{ __('Account') }}</span>
            </li>
            <li class="auth-wizard-step {{ $step === 2 ? 'active' : '' }} {{ $step > 2 ? 'done' : '' }}">
                <span class="auth-wizard-dot">2</span>
                <span class="auth-wizard-label">{{ __('Security') }}</span>
            </li>
            <li class="auth-wizard-step {{ $step === 3 ? 'active' : '' }}">
                <span class="auth-wizard-dot">3</span>
                <span class="auth-wizard-label">{{ __('Referral') }}</span>
            </li>
        </ol>

        <div class="auth-wizard-panel">
            @if ($step === 1)
                <x-auth.field for="name" :label="__('Name')" model="name" placeholder="Jane Doe" autocomplete="name" :required="true" :autofocus="true" />
                <x-auth.field for="email" :label="__('Email')" type="email" model="email" placeholder="you@example.com" autocomplete="username" :required="true" />
            @elseif ($step === 2)
                <x-auth.field for="password" :label="__('Password')" type="password" model="password" placeholder="••••••••" autocomplete="new-password" :required="true" :autofocus="true" />
                <x-auth.field for="password_confirmation" :label="__('Confirm Password')" type="password" model="password_confirmation" placeholder="••••••••" autocomplete="new-password" :required="true" />
            @else
                <x-auth.field for="ref" :label="__('Referral Code')" model="ref" placeholder="A1B2C3D4" autocomplete="off" uppercase="true" :required="true" :autofocus="true" />
            @endif
        </div>

        <div class="auth-wizard-actions d-flex align-items-center justify-content-between mb-3">
            @if ($step > 1)
                <button type="button" wire:click="previousStep" class="btn btn-outline-secondary px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i>{{ __('Back') }}
                </button>
            @else
                <span></span>
            @endif

            @if ($step < 3)
                <button type="button" wire:click="nextStep" class="btn btn-primary px-4">
                    {{ __('Continue') }}<i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            @else
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fa-solid fa-user-plus me-2"></i>{{ __('Register') }}
                </button>
            @endif
        </div>
    </form>

    <p class="text-center small text-muted mb-0">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="auth-link" wire:navigate>{{ __('Log in') }}</a>
    </p>
</div>
