<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');

        if ($impersonatorId) {
            session()->forget('impersonator_id');
            $request->session()->regenerateToken();

            Auth::login(User::findOrFail($impersonatorId));

            return redirect()->route('filament.admin.pages.dashboard');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
