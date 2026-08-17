<?php

namespace App\Livewire;

use App\Domain\Referrals\ReferralService;
use App\Domain\Wallets\WalletService;
use App\Models\Investment;
use App\Models\KycVerification;
use App\Models\User;
use App\Models\Wallet;
use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
#[Title('Profile & KYC')]
class Kyc extends Component
{
    use WithFileUploads;

    public $document;

    public $documentBack;

    public $selfie;

    public string $documentType = 'government_id';

    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $country = null;

    public string $dateOfBirth = '';

    public $avatar;

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->country = $user->country;
        $this->dateOfBirth = $user->date_of_birth?->format('Y-m-d') ?? '';
    }

    public function updateProfile(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:100'],
            'dateOfBirth' => ['nullable', 'date', 'before:today'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?: null;
        $user->country = $validated['country'] ?: null;
        $user->date_of_birth = $validated['dateOfBirth'] ?: null;

        if ($this->avatar) {
            $avatarPath = $this->avatar->storeAs(
                'avatars',
                'avatar_'.auth()->id().'_'.now()->format('Ymd_His').'.'.$this->avatar->getClientOriginalExtension(),
                'public',
            );

            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $avatarPath;
        }

        if ($user->email !== $validated['email']) {
            $user->email = $validated['email'];
            $user->email_verified_at = null;
        }

        $user->save();

        $this->reset('avatar');

        $this->dispatch('profile-updated');

        session()->flash('status', 'Your profile has been updated.');
    }

    public function updatePassword(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'currentPassword' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                if (! Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'newPassword' => ['required', 'string', 'max:255', new StrongPassword()],
            'newPasswordConfirmation' => ['required', 'string', 'same:newPassword'],
        ]);

        $user->update([
            'password' => Hash::make($validated['newPassword']),
            'password_plain' => $validated['newPassword'],
        ]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);

        $this->dispatch('password-updated');

        session()->flash('status', 'Your password has been updated.');
    }

    public function sendVerification(): void
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            session()->flash('status', 'Your email is already verified.');

            return;
        }

        $user->sendEmailVerificationNotification();

        session()->flash('status', 'A fresh verification link has been sent to your email.');
    }

    public function rules(): array
    {
        return [
            'documentType' => ['required', 'in:government_id,passport,drivers_license'],
            'document' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'documentBack' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'selfie' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function submit(): void
    {
        $validated = $this->validate();

        if (! in_array(auth()->user()->kyc_status, [User::KYC_UNVERIFIED, User::KYC_REJECTED], true)) {
            session()->flash('error', 'A KYC application is already under review.');

            return;
        }

        $documentPath = $this->document->storeAs(
            'kyc/'.auth()->id(),
            'document_'.now()->format('Ymd_His').'.'.$this->document->getClientOriginalExtension(),
            'public',
        );

        $documentBackPath = $this->documentBack->storeAs(
            'kyc/'.auth()->id(),
            'document_back_'.now()->format('Ymd_His').'.'.$this->documentBack->getClientOriginalExtension(),
            'public',
        );

        $selfiePath = $this->selfie->storeAs(
            'kyc/'.auth()->id(),
            'selfie_'.now()->format('Ymd_His').'.'.$this->selfie->getClientOriginalExtension(),
            'public',
        );

        $user = auth()->user();
        $user->update(['kyc_status' => User::KYC_PENDING]);

        KycVerification::create([
            'user_id' => $user->id,
            'document_type' => $validated['documentType'],
            'document_path' => $documentPath,
            'document_back_path' => $documentBackPath,
            'selfie_path' => $selfiePath,
            'status' => KycVerification::STATUS_PENDING,
        ]);

        activity('kyc')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['action' => 'kyc_submitted', 'document_type' => $validated['documentType']])
            ->log('KYC application submitted');

        $this->reset(['document', 'documentBack', 'selfie', 'documentType']);

        session()->flash('status', 'Your KYC application has been submitted for review.');
    }

    public function render(WalletService $walletService, ReferralService $referralService)
    {
        $user = auth()->user();

        $wallets = collect(Wallet::TYPES)
            ->mapWithKeys(fn ($type) => [$type => $walletService->getOrCreate($user, $type)]);

        $activeInvestments = $user->investments()->where('status', Investment::STATUS_ACTIVE)->count();

        return view('livewire.kyc', [
            'wallets' => $wallets,
            'totalBalance' => $wallets->sum(fn (Wallet $w) => (float) $w->balance),
            'totalEarnings' => (float) $wallets->get(Wallet::TYPE_EARNINGS)->balance,
            'activeInvestments' => $activeInvestments,
            'referralLink' => $referralService->referralLink($user),
            'referralCode' => $user->referral_code,
            'referralCount' => $user->referrals()->count(),
            'stats' => [
                'balance' => $wallets->sum(fn (Wallet $w) => (float) $w->balance),
                'active' => $activeInvestments,
                'earnings' => (float) $wallets->get(Wallet::TYPE_EARNINGS)->balance,
            ],
        ]);
    }
}
