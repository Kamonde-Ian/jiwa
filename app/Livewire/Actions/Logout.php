<?php

namespace App\Livewire\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): void
    {
        $impersonatorId = Session::get('impersonator_id');

        if ($impersonatorId) {
            Session::forget('impersonator_id');
            Auth::login(User::findOrFail($impersonatorId));
            Session::regenerateToken();

            return;
        }

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
