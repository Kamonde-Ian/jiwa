<?php

namespace App\Filament\Pages\Auth\PasswordReset;

use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;

class ResetPassword extends BaseResetPassword
{
    protected static string $view = 'filament.pages.auth.reset-password';

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::FourExtraLarge;
    }
}
