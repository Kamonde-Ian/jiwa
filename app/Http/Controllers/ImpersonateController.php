<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function enter(Request $request, User $target): \Illuminate\Http\RedirectResponse
    {
        $admin = Auth::user();

        abort_unless($admin && $admin->isAdmin(), 403);
        abort_if($target->isAdmin(), 403, 'Admins cannot be impersonated.');
        abort_if($target->id === $admin->id, 403, 'You cannot impersonate yourself.');

        $request->session()->regenerate();

        session(['impersonator_id' => $admin->id]);

        Auth::login($target);

        return redirect()->route('dashboard');
    }

    public function leave(Request $request): \Illuminate\Http\RedirectResponse
    {
        $impersonatorId = session('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $request->session()->forget('impersonator_id');
        $request->session()->regenerate();

        Auth::login(User::findOrFail($impersonatorId));

        return redirect()->route('filament.admin.pages.dashboard');
    }
}