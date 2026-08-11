<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script src="{{ asset('js/theme.js') }}"></script>
        <link rel="stylesheet" href="{{ asset('css/theme-switch.css') }}">

        <title>@yield('title', config('app.name', 'JIWA'))</title>
        <meta name="description" content="@yield('description', 'Grow your wealth with JIWA — a secure, transparent crypto investment platform with daily returns.')">

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

        @if (Vite::isRunningHot())
            @vite(['resources/css/marketing.scss', 'resources/js/marketing.js'])
        @else
            @vite(['resources/js/marketing.js'])
            <link rel="stylesheet" href="{{ Vite::asset('resources/css/marketing.scss') }}" data-navigate-track="reload">
        @endif
        <link rel="stylesheet" href="{{ asset('css/user.css') }}?v={{ filemtime(public_path('css/user.css')) }}" data-navigate-track="reload">
        @stack('styles')
    </head>
    <body class="marketing-body">
        @include('partials.loading-screen')
        @php $appEmail = 'support@'.strtolower((string) parse_url(config('app.url'), PHP_URL_HOST)); @endphp
        <nav class="marketing-nav navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}" wire:navigate>
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} logo" style="height:2.75rem;width:auto;">
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#marketingNav"
                    aria-controls="marketingNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="marketingNav">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="{{ route('home') }}" wire:navigate>Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('public.about') }}" wire:navigate>About</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('public.plans') }}" wire:navigate>Investment Plans</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('public.faq') }}" wire:navigate>FAQ</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('public.contact') }}" wire:navigate>Contact</a></li>
                    </ul>

                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                        <li class="nav-item d-flex align-items-center mb-2 mb-lg-0 ms-lg-2 me-lg-1">
                            @include('partials.theme-toggle')
                        </li>
                        @auth
                            @if (auth()->user()->hasRole('admin'))
                                <li class="nav-item"><a class="btn btn-primary btn-sm px-3" href="{{ \Filament\Facades\Filament::getPanel('admin')->getUrl() }}">Go to Admin</a></li>
                            @else
                                <li class="nav-item"><a class="btn btn-primary btn-sm px-3" href="{{ route('dashboard') }}" wire:navigate>Go to Dashboard</a></li>
                            @endif
                        @else
                            <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Log in</a></li>
                            <li class="nav-item"><a class="btn btn-primary btn-sm px-3" href="{{ route('register') }}">Get Started</a></li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <main>
            @yield('content')
        </main>

        <footer class="marketing-footer pt-5 mt-5 text-light" style="background:#18202e">
            <div class="container pb-5">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} logo" style="height:2.5rem;width:auto;">
                        </div>
                        <p class="small text-white-50 mb-3" style="max-width:21rem">
                            Empowering individuals to grow wealth through transparent, secure crypto-powered investments.
                        </p>
                        <div class="footer-social d-flex gap-2">
                            <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                            <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                            <a href="#" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="fw-semibold text-white small text-uppercase mb-3" style="letter-spacing:.4px">Explore</div>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a class="text-white-50 text-decoration-none" href="{{ route('home') }}" wire:navigate>Home</a></li>
                            <li class="mb-2"><a class="text-white-50 text-decoration-none" href="{{ route('public.about') }}" wire:navigate>About</a></li>
                            <li class="mb-2"><a class="text-white-50 text-decoration-none" href="{{ route('public.plans') }}" wire:navigate>Investment Plans</a></li>
                            <li class="mb-2"><a class="text-white-50 text-decoration-none" href="{{ route('public.faq') }}" wire:navigate>FAQ</a></li>
                        </ul>
                    </div>

                    <div class="col-6 col-lg-2">
                        <h4 class="fw-bold text-white small text-uppercase mb-3" style="letter-spacing:.4px">Legal</h4>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a class="text-white-50 text-decoration-none" href="{{ route('public.terms') }}" wire:navigate>Terms of Service</a></li>
                            <li class="mb-2"><a class="text-white-50 text-decoration-none" href="{{ route('public.privacy') }}" wire:navigate>Privacy Policy</a></li>
                            <li class="mb-2"><a class="text-white-50 text-decoration-none" href="{{ route('public.contact') }}" wire:navigate>Contact</a></li>
                        </ul>
                    </div>

                    <div class="col-12 col-lg-3">
                        <h4 class="fw-semibold text-white small text-uppercase mb-3" style="letter-spacing:.4px">Contact</h4>
                        <ul class="list-unstyled small">
                            <li class="mb-2 text-white-50"><i class="fa-solid fa-envelope me-2"></i>{{ $appEmail }}</li>
                            <li class="mb-2 text-white-50"><i class="fa-solid fa-globe me-2"></i>24/7 support</li>
                            <li class="mb-2 text-white-50"><i class="fa-solid fa-comments me-2"></i>Live chat &amp; email</li>
                        </ul>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm px-3 mt-1">Open Account</a>
                    </div>
                </div>

                <hr class="mt-4 border-secondary">

                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 small text-white-50">
                    <span>© {{ date('Y') }} {{ config('app.name', 'JIWA') }}. All rights reserved.</span>
                    <span>Invest responsibly.</span>
                </div>
            </div>
        </footer>

        </footer>

        {{-- Floating actions: socials & chatbot (left); scroll-to-top (right) --}}
        <div class="floating-actions">
            <div class="fab-social-wrap">
                <div class="social-pop" id="socialPop">
                    <a href="#" aria-label="Twitter / X"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
                <button type="button" class="fab fab-social" id="socialFab" aria-label="Social media" title="Follow us">
                    <i class="fa-solid fa-share-nodes"></i>
                </button>
            </div>

            <div class="chat-wrap">
                <div class="chat-pop" id="chatPop" role="dialog" aria-label="Chat with us">
                    <div class="chat-head">
                        <div>
                            <div class="chat-title">{{ config('app.name') }} Assistant</div>
                            <div class="chat-status"><span class="chat-dot"></span> Online · replies instantly</div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" id="chatClose" aria-label="Close chat"></button>
                    </div>
                    <div class="chat-body" id="chatBody">
                        <div class="chat-msg bot">Hi! 👋 I'm the {{ config('app.name') }} assistant. Ask me about plans, interest, withdrawals, or how to get started.</div>
                    </div>
                    <form class="chat-input" id="chatForm">
                        <input type="text" id="chatMsg" class="form-control form-control-sm" placeholder="Type your message…" autocomplete="off" maxlength="200">
                        <button type="submit" class="btn btn-primary btn-sm" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>
                <button type="button" class="fab fab-chat" id="chatFab" aria-label="Open chat">
                    <i class="fa-solid fa-comment-dots"></i>
                </button>
            </div>
        </div>

        <div class="scroll-actions">
            <button type="button" class="fab fab-scroll" id="scrollTopBtn" aria-label="Scroll to top" title="Scroll to top">
                <i class="fa-solid fa-arrow-up"></i>
            </button>
        </div>

        @yield('scripts')
    </body>
</html>