/* =====================================================================
   JIWA global theme controller.
   One preference drives the whole platform (marketing, auth, user
   dashboard and Filament admin). Dark is the default.

   - Persists to localStorage `jiwa_theme` and a `theme` cookie.
   - Mirrors the value into localStorage `theme` (used by Filament's
     own dark-mode handling) and dispatches `theme-changed` so the
     Filament switcher stays in sync.
   - Sets `data-theme`, `data-bs-theme` (Bootstrap 5.3) and the
     `dark`/`light` classes on <html>.
   ===================================================================== */

(function () {
    'use strict';

    var KEY = 'jiwa_theme';
    var DEFAULT_THEME = 'dark';

    function getStored() {
        try {
            var v = localStorage.getItem(KEY);
            return v === 'dark' || v === 'light' ? v : null;
        } catch (e) {
            return null;
        }
    }

    var current = getStored() || DEFAULT_THEME;

    function setCookie(name, value) {
        try {
            document.cookie = name + '=' + value + '; path=/; max-age=31536000; SameSite=Lax';
        } catch (e) {}
    }

    function apply(theme) {
        var root = document.documentElement;
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        root.classList.toggle('dark', theme === 'dark');
        root.classList.toggle('light', theme === 'light');
    }

    function syncCheckboxes() {
        document.querySelectorAll('.theme-switch__checkbox').forEach(function (cb) {
            cb.checked = current === 'dark';
        });
    }

    function setTheme(theme) {
        if (theme !== 'dark' && theme !== 'light') return;
        if (theme === current) {
            syncCheckboxes();
            return;
        }
        current = theme;
        apply(theme);
        try {
            localStorage.setItem(KEY, theme);
            localStorage.setItem('theme', theme);
        } catch (e) {}
        setCookie(KEY, theme);
        setCookie('theme', theme);
        syncCheckboxes();
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: theme }));
    }

    function init() {
        apply(current);
        try {
            localStorage.setItem('theme', current);
        } catch (e) {}
        setCookie('theme', current);
        syncCheckboxes();

        document.addEventListener('click', function (e) {
            var cb = e.target.closest('.theme-switch__checkbox');
            if (cb) {
                setTheme(cb.checked ? 'dark' : 'light');
                return;
            }
            var btn = e.target.closest('[data-theme-toggle]');
            if (btn) setTheme(current === 'dark' ? 'light' : 'dark');
        });

        // Keep in sync if Filament's own theme switcher changes the theme.
        window.addEventListener('theme-changed', function (e) {
            var theme = e.detail === 'system'
                ? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : e.detail;
            if (theme === 'dark' || theme === 'light') setTheme(theme);
        });

        // Re-apply the persisted theme after every Livewire SPA navigation so
        // the selection sticks when moving between pages.
        document.addEventListener('livewire:navigated', function () {
            current = getStored() || current || DEFAULT_THEME;
            apply(current);
            syncCheckboxes();
        });

        window.JIWATheme = {
            set: setTheme,
            toggle: function () { setTheme(current === 'dark' ? 'light' : 'dark'); },
            current: function () { return current; },
        };
    }

    apply(current); // before first paint to avoid a flash

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
