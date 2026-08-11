{{-- Global logo loading overlay: shows for 3.5s on page load and on every page open. --}}
<div id="app-loader" class="app-loader" aria-hidden="true">
    <div class="app-loader-inner">
        <div class="app-loader-logo-wrap">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} logo" class="app-loader-logo">
        </div>
        <div class="app-loader-dots" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>
        <div class="app-loader-text">Loading {{ config('app.name') }}…</div>
    </div>
</div>

@once
<style>
    .app-loader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fffaf1;
        opacity: 1;
        transition: opacity .45s ease;
    }

    .app-loader.loaded {
        opacity: 0;
        pointer-events: none;
    }

    .app-loader-inner {
        text-align: center;
    }

    .app-loader-logo-wrap {
        position: relative;
        display: inline-block;
    }

    .app-loader-logo {
        position: relative;
        z-index: 1;
        height: 3.25rem;
        width: auto;
        animation: appLoaderPulse 1.4s ease-in-out infinite;
    }

    .app-loader-logo-wrap::before {
        content: "";
        position: absolute;
        inset: -2.75rem;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(200, 148, 42, .3) 0%, rgba(200, 148, 42, 0) 70%);
        animation: appLoaderGlow 1.4s ease-in-out infinite;
    }

    .app-loader-dots {
        display: flex;
        justify-content: center;
        gap: .5rem;
        margin-top: 1.75rem;
    }

    .app-loader-dots span {
        width: .55rem;
        height: .55rem;
        border-radius: 50%;
        background: #C8942A;
        animation: appLoaderBounce 1.2s ease-in-out infinite;
    }

    .app-loader-dots span:nth-child(2) {
        animation-delay: .15s;
    }

    .app-loader-dots span:nth-child(3) {
        animation-delay: .3s;
    }

    .app-loader-text {
        margin-top: .8rem;
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #986817;
    }

    @keyframes appLoaderPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.06); }
    }

    @keyframes appLoaderGlow {
        0%, 100% { transform: scale(.85); opacity: .6; }
        50% { transform: scale(1.15); opacity: 1; }
    }

    @keyframes appLoaderBounce {
        0%, 100% { transform: translateY(0); opacity: .4; }
        50% { transform: translateY(-.45rem); opacity: 1; }
    }
</style>

<script>
    (function () {
        var DURATION = 3500;

        var hideTimer = null;

        function getLoader() {
            return document.getElementById('app-loader');
        }

        window.appLoaderHide = function () {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                var l = getLoader();
                if (l) l.classList.add('loaded');
            }, DURATION);
        };

        window.appLoaderShow = function () {
            clearTimeout(hideTimer);
            var l = getLoader();
            if (l) l.classList.remove('loaded');
            window.appLoaderHide();
        };

        // Re-show on every Livewire (wire:navigate) page transition.
        document.addEventListener('livewire:navigating', function () {
            window.appLoaderShow();
        });

        window.appLoaderHide();
    })();
</script>
@endonce
