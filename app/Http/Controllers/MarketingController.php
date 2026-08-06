<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class MarketingController extends Controller
{
    public function contact(): \Illuminate\View\View
    {
        return view('marketing.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $key = 'contact|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Please wait a minute before sending another message.'),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        RateLimiter::hit($key, 60);

        ContactMessage::create([
            ...$validated,
            'ip_address' => $request->ip(),
        ]);

        return Redirect::route('public.contact')
            ->with('status', __('Thank you. Your message has been received and we will be in touch shortly.'));
    }
}