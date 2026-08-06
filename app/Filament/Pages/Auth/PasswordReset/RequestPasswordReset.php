<?php

namespace App\Filament\Pages\Auth\PasswordReset;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected static string $view = 'filament.pages.auth.request-password-reset';

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
