<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script src="{{ asset('js/theme.js') }}"></script>
        <link rel="stylesheet" href="{{ asset('css/theme-switch.css') }}">

        <title>{{ isset($title) && $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

        @if (Vite::isRunningHot())
            @vite(['resources/css/sneat.scss', 'resources/js/sneat.js'])
        @else
            @vite(['resources/js/sneat.js'])
            <link rel="stylesheet" href="{{ Vite::asset('resources/css/sneat.scss') }}" data-navigate-track="reload">
        @endif
        <link rel="stylesheet" href="{{ asset('css/user.css') }}?v={{ filemtime(public_path('css/user.css')) }}" data-navigate-track="reload">
        @stack('styles')
    </head>
    <body>
        @include('partials.loading-screen')
        <div class="layout-wrapper">
            @include('layouts.partials.sidebar')
            <div class="layout-sidebar-backdrop" id="layout-sidebar-backdrop" aria-hidden="true"></div>

            <div class="layout-page">
                @include('layouts.partials.topbar')

                <div class="layout-content">
                    <main>
                        @if (isset($header))
                            <div class="mb-4">
                                <h4 class="mb-1 fw-semibold">{{ $header }}</h4>
                                @isset($subHeader)
                                    <p class="mb-0 text-muted">{{ $subHeader }}</p>
                                @endisset
                            </div>
                        @endif

                        {{ $slot }}
                    </main>

                    <footer class="footer mt-4">
                        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </footer>
                </div>
            </div>
        </div>

        @livewire('notifications')
        @stack('scripts')
    </body>
</html>
