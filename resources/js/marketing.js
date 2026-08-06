import './sneat.js';

// --- Auth card glow + input glow (user auth pages) ---
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

    document.querySelectorAll('[data-auth-input]').forEach((wrap) => {
        if (wrap.dataset.authInputInit) return;
        wrap.dataset.authInputInit = '1';

        const top = wrap.querySelector('[data-glow-top]');
        const bottom = wrap.querySelector('[data-glow-bottom]');

        const paint = (x) => {
            if (top) {
                top.style.background = `radial-gradient(30px circle at ${x}px 0px, var(--color-text-primary) 0%, transparent 70%)`;
                top.classList.add('show');
            }
            if (bottom) {
                bottom.style.background = `radial-gradient(30px circle at ${x}px 2px, var(--color-text-primary) 0%, transparent 70%)`;
                bottom.classList.add('show');
            }
        };

        wrap.addEventListener('mousemove', (e) => {
            const r = wrap.getBoundingClientRect();
            paint(e.clientX - r.left);
        });
        wrap.addEventListener('mouseleave', () => {
            if (top) top.classList.remove('show');
            if (bottom) bottom.classList.remove('show');
        });
    });
}

// Re-run init after DOM changes (e.g. wizard step switches add new fields).
function watchAuthGlow() {
    initAuthGlow();
    const observer = new MutationObserver(() => initAuthGlow());
    observer.observe(document.body, { childList: true, subtree: true });
}

document.addEventListener('DOMContentLoaded', watchAuthGlow);
document.addEventListener('livewire:init', watchAuthGlow);
document.addEventListener('livewire:navigated', watchAuthGlow);

document.addEventListener('DOMContentLoaded', () => {
    const scrollBtn = document.getElementById('scrollTopBtn');
    const actions = scrollBtn ? scrollBtn.closest('.scroll-actions, .floating-actions') : null;

    // Scroll-to-top visibility
    const onScroll = () => {
        if (actions) actions.classList.toggle('show-scroll', window.scrollY > 400);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (scrollBtn) scrollBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    // Social media popup toggle
    const socialFab = document.getElementById('socialFab');
    const socialWrap = document.querySelector('.fab-social-wrap');
    const chatWrap = document.querySelector('.chat-wrap');
    const openChat = () => chatWrap?.classList.add('open');
    const closeChat = () => chatWrap?.classList.remove('open');

    if (socialFab) socialFab.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = socialWrap?.classList.contains('open');
        if (socialWrap) socialWrap.classList.toggle('open');
        if (!isOpen) closeChat();
    });

    // Chatbot toggle
    const chatFab = document.getElementById('chatFab');
    const chatClose = document.getElementById('chatClose');
    chatFab?.addEventListener('click', (e) => { e.stopPropagation(); openChat(); socialWrap?.classList.remove('open'); });
    chatClose?.addEventListener('click', closeChat);

    document.addEventListener('click', (e) => {
        if (socialFab && !socialFab.contains(e.target) && !e.target.closest('.social-pop')) {
            socialWrap?.classList.remove('open');
        }
        if (chatFab && !chatFab.contains(e.target) && !e.target.closest('.chat-pop')) {
            closeChat();
        }
    });

    // --- Prototype chatbot ---
    const chatForm = document.getElementById('chatForm');
    const chatBody = document.getElementById('chatBody');
    const chatMsg = document.getElementById('chatMsg');

    const reply = (input) => {
        const s = input.toLowerCase();
        if (/(^|\s)hi|hello|hey\b/.test(s) || /how are you/.test(s)) {
            return "Hi there! 😊 Ask me about our investment plans, daily interest, withdrawals, or how to get started.";
        }
        if (/interest|return|earn|rate|percent|%/.test(s)) {
            return "Plans earn a fixed 0.5% daily interest, credited to your earnings wallet every 24 hours. Rates are admin-configurable.";
        }
if (/plans?|invest|minimum|start|how .* work/.test(s)) {
                    return "You can start from just $50. Choose a plan (30, 90, 180, 365, or 730 days), fund your wallet, then invest from your principal balance.";
                }
                if (/withdraw|withdrawal|cash/.test(s)) {
                    return "Earnings and referral income are withdrawable anytime. Complete KYC and enable 2FA to request withdrawals.";
                }
                if (/refer|referral|friend|commission/.test(s)) {
                    return "Share your unique referral link. Once you have an active investment of $100 or more, your link unlocks and you earn a 3% commission on qualifying investments.";
                }
                if (/wallet/.test(s)) {
                    return "Each account has three wallets: Principal (locked until maturity), Earnings (daily interest), and Referral (commission income).";
                }
                if (/secure|safe|security|kyc|2fa|two[ -]?factor/.test(s)) {
                    return "We use 2FA, KYC verification, TLS, and a full audit log to keep every wallet and transaction secure.";
                }
                if (/human|agent|person|support|real/.test(s)) {
                    return "Need a human? Head to the Contact page and send us a message — we reply within hours.";
                }
        if (/coins?|crypto|btc|eth|usdt|bnb|bitcoin|ethereum/.test(s)) {
            return "We support Bitcoin (BTC), Ethereum (ETH), BNB (BEP-20), and USDT on TRC-20, ERC-20, and BEP-20 networks.";
        }
        return "I can help with plans, daily interest, wallets, withdrawals, referrals, security, and supported coins. Try asking 'What is the daily interest?' 😊";
    };

    const appendMsg = (text, who) => {
        const el = document.createElement('div');
        el.className = 'chat-msg ' + who;
        el.textContent = text;
        chatBody?.appendChild(el);
        chatBody?.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
    };

    chatForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = chatMsg?.value.trim();
        if (!text) return;
        appendMsg(text, 'user');
        chatMsg.value = '';
        setTimeout(() => appendMsg(reply(text), 'bot'), 450);
    });
});