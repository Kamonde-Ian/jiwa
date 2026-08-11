<aside class="layout-sidebar">
    <div class="app-brand">
        <a href="{{ route('dashboard') }}" class="app-brand-link" wire:navigate>
            <span class="app-brand-logo"><img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} logo" style="height:2.75rem;width:auto;"></span>
        </a>
    </div>

    <ul class="menu list-unstyled mb-0">
        <li class="menu-header">Overview</li>

        <li class="menu-item">
            <a href="{{ route('dashboard') }}" class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">Finance</li>

        <li class="menu-item">
            <a href="{{ route('wallets') }}" class="menu-link {{ request()->routeIs('wallets') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-wallet"></i>
                <span>My Wallets</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="{{ route('invest') }}" class="menu-link {{ request()->routeIs('invest') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-arrow-trend-up"></i>
                <span>Invest</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="{{ route('investments.index') }}" class="menu-link {{ request()->routeIs('investments.*') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-layer-group"></i>
                <span>My Investments</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="{{ route('deposits.index') }}" class="menu-link {{ request()->routeIs('deposits.*') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-circle-down"></i>
                <span>Deposits</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="{{ route('withdrawals.index') }}" class="menu-link {{ request()->routeIs('withdrawals.*') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-circle-up"></i>
                <span>Withdrawals</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="{{ route('referrals') }}" class="menu-link {{ request()->routeIs('referrals') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-user-group"></i>
                <span>Referrals</span>
            </a>
        </li>

        <li class="menu-header">Account</li>

        <li class="menu-item">
            <a href="{{ route('profile') }}" class="menu-link {{ request()->routeIs('profile') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-user-gear"></i>
                <span>Profile &amp; KYC</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="{{ route('security') }}" class="menu-link {{ request()->routeIs('security') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-shield-halved"></i>
                <span>Security (2FA)</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="{{ route('statements.index') }}" class="menu-link {{ request()->routeIs('statements.*') ? 'active' : '' }}" wire:navigate>
                <i class="fa-solid fa-file-invoice"></i>
                <span>Statements</span>
            </a>
        </li>
    </ul>
</aside>
