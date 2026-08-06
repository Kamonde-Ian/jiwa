<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const KYC_UNVERIFIED = 'unverified';
    public const KYC_PENDING = 'pending';
    public const KYC_VERIFIED = 'verified';
    public const KYC_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country',
        'date_of_birth',
        'avatar_path',
        'referral_code',
        'referred_by',
        'kyc_status',
        'two_factor_enabled',
        'google2fa_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google2fa_secret' => 'encrypted',
            'two_factor_enabled' => 'boolean',
            'date_of_birth' => 'date',
        ];
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class);
    }

    public function latestKycVerification()
    {
        return $this->hasOne(KycVerification::class)->latestOfMany();
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function referralEarnings(): HasMany
    {
        return $this->hasMany(ReferralEarning::class, 'referrer_id');
    }

    public function isKycVerified(): bool
    {
        return $this->kyc_status === self::KYC_VERIFIED;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isAdmin();
        }

        return true;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }
}
