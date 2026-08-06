/* Shared auth interactions for the Filament admin auth pages (login + password reset).
   Mirrors the user auth glow in resources/js/marketing.js, but self-contained so the
   admin pages (which do not load the Vite marketing bundle) get the same effects. */
(function () {
    function initAuthGlow() {
        document.querySelectorAll('[data-auth-card]').forEach((card) => {
            if (card.dataset.authGlowInit) return;
            card.dataset.authGlowInit = '1';

            const blob = card.querySelector('[data-auth-card-glow]');
            if (blob) {
                card.addEventListener('mousemove', (e) => {
                    const r = card.getBoundingClientRect();
                    blob.style.transform = `translate(${e.clientX - r.left - 250}px, ${e.clientY - r.top - 250}px)`;
                });
                card.addEventListener('mouseenter', () => card.classList.add('auth-card-hovering'));
                card.addEventListener('mouseleave', () => card.classList.remove('auth-card-hovering'));
            }
        });

        document.querySelectorAll('.fi-auth .fi-input-wrp').forEach((wrap) => {
            if (wrap.dataset.authInputInit) return;
            wrap.dataset.authInputInit = '1';
            wrap.style.position = 'relative';

            const top = document.createElement('span');
            top.className = 'auth-field-glow auth-field-glow-top';
            const bottom = document.createElement('span');
            bottom.className = 'auth-field-glow auth-field-glow-bottom';
            wrap.appendChild(top);
            wrap.appendChild(bottom);

            const paint = (x) => {
                top.style.background = `radial-gradient(30px circle at ${x}px 0px, var(--color-text-primary) 0%, transparent 70%)`;
                bottom.style.background = `radial-gradient(30px circle at ${x}px 2px, var(--color-text-primary) 0%, transparent 70%)`;
                top.classList.add('show');
                bottom.classList.add('show');
            };
            wrap.addEventListener('mousemove', (e) => {
                const r = wrap.getBoundingClientRect();
                paint(e.clientX - r.left);
            });
            wrap.addEventListener('mouseleave', () => {
                top.classList.remove('show');
                bottom.classList.remove('show');
            });
        });
    }

    // Re-run after DOM changes (Livewire morphs can replace form nodes).
    function watchAuthGlow() {
        initAuthGlow();
        const observer = new MutationObserver(() => initAuthGlow());
        observer.observe(document.body, { childList: true, subtree: true });
    }

    document.addEventListener('DOMContentLoaded', watchAuthGlow);
    document.addEventListener('livewire:init', watchAuthGlow);
})();
