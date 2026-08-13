<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script src="{{ asset('js/theme.js') }}"></script>
        <link rel="stylesheet" href="{{ asset('css/theme-switch.css') }}">

        <title>{{ $title ?? config('app.name', 'JIWA') }} — {{ config('app.name') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&display=swap" rel="stylesheet">

        @if (Vite::isRunningHot())
            @vite(['resources/css/marketing.scss', 'resources/js/marketing.js'])
        @else
            @vite(['resources/js/marketing.js'])
            <link rel="stylesheet" href="{{ Vite::asset('resources/css/marketing.scss') }}" data-navigate-track="reload">
        @endif
        <link rel="stylesheet" href="{{ asset('css/auth.css') }}" data-navigate-track="reload">
        @stack('title')
        @stack('styles')
    </head>
    <body class="marketing-body auth-body">
        @include('partials.loading-screen')
        <div class="auth-shell" style="position:relative">
            <div style="position:absolute;top:1.25rem;right:1.5rem;z-index:5">
                @include('partials.theme-toggle', ['size' => 'theme-switch--xs'])
            </div>
            <div class="auth-card" data-auth-card>
                <div class="auth-card-glow" data-auth-card-glow></div>

                <div class="auth-card-left">
                    <div class="auth-card-inner">
                        <div class="auth-brand">
                            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} logo" style="height:3.5rem;width:auto;">
                        </div>

                        @if (filled($brand ?? null))
                            <div class="text-center mb-4">
                                <h1 class="auth-heading mb-1">{{ $brand }}</h1>
                                <p class="auth-subtitle">{{ $subtitle ?? '' }}</p>
                            </div>
                        @endif

                        {{ $slot }}

                        <p class="auth-footer text-center mt-3 mb-0">
                            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </p>
                    </div>
                </div>

                <div class="auth-card-right" aria-hidden="true">
                    <div class="auth-visual">
                        <svg class="auth-visual-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2 4 8v8l8 6 8-6V8l-8-6zm0 2.5L18 7.6v8.8l-6 4.5-6-4.5V7.6l6-3.1z"/><path d="M11.2 7.8v8.4h1.6V7.8h-1.6zM8.5 9.9l.9 1.3 2.6-1.8V8.1L8.5 9.9z"/>
                        </svg>
                        <div class="auth-visual-caption">
                            <strong>Grow your crypto wealth</strong>
                            <span>Secure investments with daily interest, instant withdrawals and 24/7 trading.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
