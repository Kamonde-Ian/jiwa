<nav class="layout-navbar">
    <div class="container-fluid d-flex align-items-center justify-content-between h-100 px-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary border-0 d-lg-none me-1 layout-menu-toggle" type="button" title="Toggle menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary border-0 d-none d-lg-inline-flex me-1 layout-menu-toggle" type="button" title="Collapse menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div class="d-flex align-items-center gap-3">
            @include('partials.theme-toggle', ['size' => 'theme-switch--xs'])

            <a href="{{ route('deposits.index') }}" class="btn btn-primary btn-sm" wire:navigate>
                <i class="fa-solid fa-circle-down me-1"></i> Deposit
            </a>

            <div class="dropdown">
                <button class="btn dropdown-toggle d-flex align-items-center gap-2 p-0 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    @if (auth()->user()->avatar_path)
                        <img src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}"
                            class="avatar rounded-circle border bg-white object-fit-cover" style="width: 2rem; height: 2rem;">
                    @else
                        <span class="avatar d-inline-flex align-items-center justify-content-center rounded bg-primary text-white" style="width: 2rem; height: 2rem;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    @endif
                    <span class="d-none d-sm-inline text-body">{{ auth()->user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile') }}" wire:navigate><i class="fa-regular fa-user me-2"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="{{ route('security') }}" wire:navigate><i class="fa-solid fa-shield-halved me-2"></i> Security</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="fa-solid fa-right-from-bracket me-2"></i> Log Out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
