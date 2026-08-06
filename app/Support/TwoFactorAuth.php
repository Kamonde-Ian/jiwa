<?php

namespace App\Support;

use App\Models\User;
use PragmaRX\Google2FALaravel\Google2FA;

class TwoFactorAuth
{
    public function __construct(protected Google2FA $google2fa)
    {
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $qr = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret,
        );

        if (str_starts_with($qr, '<')) {
            return $qr;
        }

        return sprintf(
            '<img src="%s" alt="%s two-factor QR code" width="200" height="200" class="img-fluid">',
            $qr,
            e(config('app.name')),
        );
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function isEnabled(User $user): bool
    {
        return (bool) $user->two_factor_enabled;
    }

    /**
     * 2FA is mandatory before a user may request a withdrawal.
     */
    public function canWithdraw(User $user): bool
    {
        return $this->isEnabled($user);
    }

    public function enable(User $user, string $secret, string $code): bool
    {
        if (! $this->verify($secret, $code)) {
            return false;
        }

        $user->forceFill([
            'google2fa_secret' => $secret,
            'two_factor_enabled' => true,
        ])->save();

        activity('security')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['action' => 'two_factor_enabled'])
            ->log('Two-factor authentication enabled');

        return true;
    }

    public function disable(User $user, string $code): bool
    {
        if (! $this->verify($user->google2fa_secret, $code)) {
            return false;
        }

        $user->forceFill([
            'google2fa_secret' => null,
            'two_factor_enabled' => false,
        ])->save();

        activity('security')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['action' => 'two_factor_disabled'])
            ->log('Two-factor authentication disabled');

        return true;
    }
}
