<div>
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php $user = auth()->user(); @endphp

    @include('livewire.partials.page-hero', [
        'title' => 'Profile',
        'subtitle' => 'Manage your account details, share your referral link and keep your identity verified.',
        'stats' => [
            ['label' => 'Total Balance', 'value' => '$' . number_format($stats['balance'], 2)],
            ['label' => 'Active Investments', 'value' => $stats['active']],
            ['label' => 'Total Earnings', 'value' => '$' . number_format($stats['earnings'], 2)],
        ],
    ])

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="position-relative d-inline-block mb-3">
                        @if ($user->avatar_path)
                            <img src="{{ Storage::url($user->avatar_path) }}" alt="{{ $user->name }}"
                                class="avatar d-inline-flex align-items-center justify-content-center rounded-circle border bg-white object-fit-cover"
                                style="width: 5rem; height: 5rem;">
                        @else
                            <div class="avatar d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white"
                                style="width: 5rem; height: 5rem; font-size: 2rem;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                        <label for="avatarInput"
                            class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 2.1rem; height: 2.1rem;" title="Upload photo">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                        <input type="file" id="avatarInput" wire:model="avatar" accept="image/*" class="d-none">
                    </div>
                    <div wire:loading wire:target="avatar" class="small text-muted mb-2">Uploading photo…</div>
                    @error('avatar') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted small mb-3">{{ $user->email }}</p>

                    <div class="d-flex flex-wrap justify-content-center gap-1 mb-3">
                        @if ($user->email_verified_at)
                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-envelope-circle-check me-1"></i>Email Verified</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fa-regular fa-envelope me-1"></i>Email Unverified</span>
                        @endif
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="fa-solid fa-id-card me-1"></i>KYC: {{ ucfirst($user->kyc_status) }}
                        </span>
                        <span class="badge {{ $user->two_factor_enabled ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                            <i class="fa-solid fa-shield-halved me-1"></i>2FA: {{ $user->two_factor_enabled ? 'On' : 'Off' }}
                        </span>
                    </div>

                    <div class="text-start small">
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Phone</span>
                            <span class="fw-semibold">{{ $user->phone ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Country</span>
                            <span class="fw-semibold">{{ $user->country ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Date of birth</span>
                            <span class="fw-semibold">{{ $user->date_of_birth?->format('M j, Y') ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Referral code</span>
                            <span class="fw-semibold font-monospace">{{ $referralCode ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Member since</span>
                            <span class="fw-semibold">{{ $user->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Referred by</span>
                            <span class="fw-semibold">{{ $user->referredBy?->name ?: '—' }}</span>
                        </div>
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <a href="{{ route('security') }}" class="btn btn-sm btn-outline-primary" wire:navigate><i class="fa-solid fa-shield-halved me-1"></i> Security &amp; 2FA</a>
                        <a href="{{ route('wallets') }}" class="btn btn-sm btn-outline-primary" wire:navigate><i class="fa-solid fa-wallet me-1"></i> My Wallets</a>
                        <a href="{{ route('statements.index') }}" class="btn btn-sm btn-outline-primary" wire:navigate><i class="fa-solid fa-file-invoice me-1"></i> Statements</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5>Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form wire:submit="updateProfile">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label small fw-semibold">Full Name</label>
                                <input wire:model="name" id="name" type="text" class="form-control" autocomplete="name">
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label small fw-semibold">Email Address</label>
                                <input wire:model="email" id="email" type="email" class="form-control" autocomplete="username">
                                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @if ($user->email_verified_at)
                                    <div class="mt-2 small text-success"><i class="fa-solid fa-circle-check me-1"></i>Email verified</div>
                                @else
                                    <div class="mt-2 d-flex align-items-center gap-2 small">
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-regular fa-envelope me-1"></i>Unverified</span>
                                        <button type="button" wire:click="sendVerification" wire:loading.attr="disabled" class="btn btn-link btn-sm p-0 text-decoration-none">
                                            Resend verification email
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label small fw-semibold">Phone Number</label>
                                <input wire:model="phone" id="phone" type="tel" class="form-control" placeholder="+1 555 000 0000">
                                @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="country" class="form-label small fw-semibold">Country</label>
                                <select wire:model="country" id="country" class="form-select">
                                    <option value="">Select a country…</option>
                                    @foreach (config('jiwa.countries') as $country)
                                        <option value="{{ $country }}">{{ $country }}</option>
                                    @endforeach
                                </select>
                                @error('country') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="dateOfBirth" class="form-label small fw-semibold">Date of Birth</label>
                                <input wire:model="dateOfBirth" id="dateOfBirth" type="date" class="form-control">
                                @error('dateOfBirth') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-4">
                            <button type="submit" class="btn btn-primary invest-cta">
                                <i class="fa-regular fa-floppy-disk me-1"></i> Save Changes
                            </button>
                            <div wire:loading wire:target="updateProfile" class="small text-muted">Saving…</div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>Change Password</h5>
                </div>
                <div class="card-body">
                    <form wire:submit="updatePassword" class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="currentPassword" class="form-label small fw-semibold">Current Password</label>
                            <input wire:model="currentPassword" type="password" id="currentPassword" class="form-control" autocomplete="current-password">
                            @error('currentPassword') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6"></div>
                        <div class="col-12 col-md-6">
                            <label for="newPassword" class="form-label small fw-semibold">New Password</label>
                            <input wire:model="newPassword" type="password" id="newPassword" class="form-control" autocomplete="new-password">
                            @error('newPassword') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="newPasswordConfirmation" class="form-label small fw-semibold">Confirm New Password</label>
                            <input wire:model="newPasswordConfirmation" type="password" id="newPasswordConfirmation" class="form-control" autocomplete="new-password">
                            @error('newPasswordConfirmation') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fa-solid fa-key me-1"></i> Update Password
                            </button>
                            <div wire:loading wire:target="updatePassword" class="small text-muted d-inline ms-2">Updating…</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Referral Program</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Earn <strong class="text-body">{{ number_format(config('jiwa.referral_commission_rate') * 100, 1) }}%</strong>
                        commission when the people you refer invest. Share your code or link to start earning.
                    </p>
                    <label class="form-label small fw-semibold" for="referral-code-input">Your referral code</label>
                    <div class="input-group mb-3">
                        <input type="text" readonly id="referral-code-input" class="form-control font-monospace text-uppercase" value="{{ $referralCode ?: '—' }}">
                        @if ($referralCode)
                            <button type="button" class="btn btn-outline-primary" data-copy="{{ $referralCode }}" data-copy-label="Copy" title="Copy code">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        @endif
                    </div>
                    <label class="form-label small fw-semibold" for="referral-link-input">Your referral link</label>
                    <div class="input-group">
                        <input type="text" readonly id="referral-link-input" class="form-control small" value="{{ $referralCode ? $referralLink : 'Fund an active investment to unlock your link' }}">
                        @if ($referralCode)
                            <button type="button" class="btn btn-outline-primary" data-copy="{{ $referralLink }}" data-copy-label="Copy" title="Copy link">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        @endif
                    </div>
                    <div class="row g-2 mt-3 pt-3 border-top">
                        <div class="col-4 text-center">
                            <div class="small text-muted">Referrals</div>
                            <div class="fw-semibold">{{ $referralCount }}</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="small text-muted">Rate</div>
                            <div class="fw-semibold">{{ number_format(config('jiwa.referral_commission_rate') * 100, 1) }}%</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="small text-muted">Earned</div>
                            <div class="fw-semibold">${{ number_format($user->referralEarnings()->sum('amount'), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Account Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Total Balance</span>
                        <span class="fw-semibold">${{ number_format($totalBalance, 2) }}</span>
                    </div>
                    @foreach ($wallets as $type => $wallet)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted">{{ ucfirst($type) }} Wallet</span>
                            <span class="fw-semibold">${{ number_format((float) $wallet->balance, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Active Investments</span>
                        <span class="fw-semibold">{{ $activeInvestments }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Total Earnings</span>
                        <span class="fw-semibold text-success">${{ number_format($totalEarnings, 2) }}</span>
                    </div>

                    <div class="d-grid gap-2 mt-3 pt-3 border-top">
                        <a href="{{ route('invest') }}" class="btn btn-primary invest-cta" wire:navigate>
                            <i class="fa-solid fa-arrow-trend-up me-1"></i> Make a Deposit &amp; Invest
                        </a>
                        <a href="{{ route('withdrawals.index') }}" class="btn btn-outline-primary" wire:navigate>
                            <i class="fa-solid fa-arrow-up me-1"></i> Withdraw Funds
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-divider mt-4 mb-3">
        <span class="section-title">
            <i class="fa-solid fa-id-card"></i>
            <span>
                Identity &amp; Verification
                <small>Confirm your identity to keep withdrawals unlocked.</small>
            </span>
        </span>
    </div>

    @php
        $status = $user->kyc_status;
        $lastRejection = $user->latestKycVerification?->rejection_reason;
        $checks = [
            ['label' => 'Email verified', 'done' => (bool) $user->email_verified_at, 'icon' => 'fa-envelope-circle-check'],
            ['label' => 'Two-factor authentication', 'done' => (bool) $user->two_factor_enabled, 'icon' => 'fa-shield-halved'],
            ['label' => 'Identity documents submitted', 'done' => in_array($status, ['pending', 'verified'], true), 'icon' => 'fa-file-circle-check'],
            ['label' => 'Identity approved', 'done' => $status === 'verified', 'icon' => 'fa-id-badge'],
        ];
        $doneCount = collect($checks)->where('done', true)->count();
    @endphp

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100 overflow-hidden">
                <div class="status-strip {{ match($status) { 'verified' => 'bg-success-subtle text-success', 'pending' => 'bg-warning-subtle text-warning', 'rejected' => 'bg-danger-subtle text-danger', default => 'bg-primary-subtle text-primary' } }}">
                    <i class="fa-solid {{ match($status) { 'verified' => 'fa-circle-check', 'pending' => 'fa-hourglass-half', 'rejected' => 'fa-circle-xmark', default => 'fa-user-shield' } }}"></i>
                    <div>
                        <div class="small opacity-75 text-uppercase">KYC Status</div>
                        <h4 class="mb-0 text-capitalize">{{ $status }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        @if ($status === 'verified')
                            Your identity has been verified. You can invest and withdraw without restrictions.
                        @elseif ($status === 'pending')
                            Your application is under review. We typically respond within 24 hours.
                        @elseif ($status === 'rejected')
                            Your application was rejected. Please resubmit with valid, readable documents.
                        @else
                            Complete the checklist below and submit your documents to unlock withdrawals.
                        @endif
                    </p>

                    @if ($status === 'rejected' && $lastRejection)
                        <div class="alert alert-danger text-start mb-3 small py-2">
                            <strong>Reason:</strong> {{ $lastRejection }}
                        </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-muted">Verification progress</span>
                        <span class="fw-semibold">{{ $doneCount }}/{{ count($checks) }}</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar {{ $status === 'rejected' ? 'bg-danger' : ($status === 'verified' ? 'bg-success' : 'bg-primary') }}"
                            style="width: {{ ($doneCount / max(count($checks), 1)) * 100 }}%"></div>
                    </div>

                    <div class="pt-2">
                        @foreach ($checks as $check)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                                <span><i class="fa-regular {{ $check['icon'] }} me-2 text-muted"></i>{{ $check['label'] }}</span>
                                @if ($check['done'])
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                @else
                                    <i class="fa-regular fa-circle text-secondary"></i>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Identity Verification</h5>
                </div>
                <div class="card-body">
                    @if (in_array($status, ['unverified', 'rejected'], true))
                        <form wire:submit="submit">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Document Type</label>
                                <div class="row g-2">
                                    @foreach ([
                                        'government_id' => ['Government ID', 'fa-id-card'],
                                        'passport' => ['Passport', 'fa-passport'],
                                        'drivers_license' => ["Driver's License", 'fa-car'],
                                    ] as $key => [$label, $icon])
                                        <div class="col-6 col-md-4">
                                            <button type="button"
                                                wire:click="$set('documentType', '{{ $key }}')"
                                                class="doc-type-card {{ $documentType === $key ? 'selected' : '' }}">
                                                <i class="fa-solid {{ $icon }}"></i>
                                                <span>{{ $label }}</span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                @error('documentType') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="document" class="form-label small fw-semibold">ID Document (front)</label>
                                    <label for="document" class="upload-zone mb-1 {{ $document ? 'has-file' : '' }}">
                                        <input wire:model="document" type="file" id="document" accept="image/*" class="d-none">
                                        <i class="fa-solid {{ $document ? 'fa-file-circle-check' : 'fa-cloud-arrow-up' }}"></i>
                                        <span>{{ $document ? $document->getClientOriginalName() : 'Click to upload the front of your ID' }}</span>
                                        <small>JPG, PNG or WEBP &middot; max 4 MB</small>
                                        <div wire:loading wire:target="document" class="small fw-semibold">Uploading…</div>
                                    </label>
                                    @error('document') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="documentBack" class="form-label small fw-semibold">ID Document (back)</label>
                                    <label for="documentBack" class="upload-zone mb-1 {{ $documentBack ? 'has-file' : '' }}">
                                        <input wire:model="documentBack" type="file" id="documentBack" accept="image/*" class="d-none">
                                        <i class="fa-solid {{ $documentBack ? 'fa-file-circle-check' : 'fa-cloud-arrow-up' }}"></i>
                                        <span>{{ $documentBack ? $documentBack->getClientOriginalName() : 'Click to upload the back of your ID' }}</span>
                                        <small>JPG, PNG or WEBP &middot; max 4 MB</small>
                                        <div wire:loading wire:target="documentBack" class="small fw-semibold">Uploading…</div>
                                    </label>
                                    @error('documentBack') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="selfie" class="form-label small fw-semibold">Selfie (holding your ID)</label>
                                <label for="selfie" class="upload-zone mb-1 {{ $selfie ? 'has-file' : '' }}">
                                    <input wire:model="selfie" type="file" id="selfie" accept="image/*" class="d-none">
                                    <i class="fa-solid {{ $selfie ? 'fa-user-check' : 'fa-camera' }}"></i>
                                    <span>{{ $selfie ? $selfie->getClientOriginalName() : 'Click to upload your selfie' }}</span>
                                    <small>Face and ID must both be clearly visible</small>
                                    <div wire:loading wire:target="selfie" class="small fw-semibold">Uploading…</div>
                                </label>
                                @error('selfie') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <button type="submit" class="btn btn-primary invest-cta" wire:loading.attr="disabled">
                                <i class="fa-solid fa-paper-plane me-1"></i> Submit Application
                            </button>
                        </form>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid {{ $status === 'verified' ? 'fa-circle-check text-success' : 'fa-hourglass-half text-warning' }} fa-2x mb-3 d-block"></i>
                            <p class="mb-0">
                                @if ($status === 'verified')
                                    Your identity is verified. You're all set to invest and withdraw.
                                @else
                                    Your application is under review. We typically respond within 24 hours.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
