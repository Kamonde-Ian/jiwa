<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

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

    public function fillDemo(): void
    {
        $this->data = [
            'email' => 'admin@jiwa.test',
            'password' => 'password',
            'remember' => true,
        ];
    }
}
