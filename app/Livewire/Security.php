<?php

namespace App\Livewire;

use App\Support\TwoFactorAuth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.dashboard')]
#[Title('Security & 2FA')]
class Security extends Component
{
    public bool $showSetup = false;

    public ?string $pendingSecret = null;

    public string $verificationCode = '';

    public string $disableCode = '';

    public function beginSetup(TwoFactorAuth $twoFactorAuth): void
    {
        $this->pendingSecret = $twoFactorAuth->generateSecret();
        $this->showSetup = true;
    }

    public function confirmSetup(TwoFactorAuth $twoFactorAuth): void
    {
        $this->validate([
            'verificationCode' => ['required', 'string', 'size:6'],
        ]);

        if (! $twoFactorAuth->enable(auth()->user(), $this->pendingSecret, $this->verificationCode)) {
            $this->addError('verificationCode', 'The code you entered is incorrect.');

            return;
        }

        session()->flash('status', 'Two-factor authentication enabled.');

        $this->reset(['pendingSecret', 'verificationCode', 'showSetup']);
    }

    public function disable(TwoFactorAuth $twoFactorAuth): void
    {
        $this->validate([
            'disableCode' => ['required', 'string', 'size:6'],
        ]);

        if (! $twoFactorAuth->disable(auth()->user(), $this->disableCode)) {
            $this->addError('disableCode', 'The code you entered is incorrect.');

            return;
        }

        session()->flash('status', 'Two-factor authentication disabled.');

        $this->reset('disableCode');
    }

    public function render()
    {
        return view('livewire.security');
    }
}
