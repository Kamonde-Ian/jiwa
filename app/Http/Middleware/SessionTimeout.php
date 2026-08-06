<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log users out after a configurable period of inactivity.
 */
class SessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $timeoutMinutes = (int) config('jiwa.session_timeout_minutes', 60);

        if ($timeoutMinutes > 0 && Auth::check()) {
            $lastActivity = $request->session()->get('last_activity');

            if ($lastActivity === null) {
                $request->session()->put('last_activity', now());

                return $next($request);
            }

            if (abs(now()->diffInMinutes($lastActivity)) >= $timeoutMinutes) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('status', __('You were logged out due to inactivity.'));
            }

            $request->session()->put('last_activity', now());
        }

        return $next($request);
    }
}
