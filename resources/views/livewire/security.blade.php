<div>
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $user = auth()->user();
        $twoFactorOn = (bool) $user->two_factor_enabled;
        $kycStatus = $user->kyc_status;
        $checks = [
            ['label' => 'Email verified', 'done' => (bool) $user->email_verified_at, 'icon' => 'fa-envelope-circle-check'],
            ['label' => 'Two-factor authentication', 'done' => $twoFactorOn, 'icon' => 'fa-shield-halved'],
            ['label' => 'Identity documents submitted', 'done' => in_array($kycStatus, ['pending', 'verified'], true), 'icon' => 'fa-file-circle-check'],
            ['label' => 'Identity approved', 'done' => $kycStatus === 'verified', 'icon' => 'fa-id-badge'],
        ];
        $doneCount = collect($checks)->where('done', true)->count();
        $requirementsMet = $doneCount === count($checks);
    @endphp

    @include('livewire.partials.page-hero', [
        'title' => 'Security',
        'subtitle' => 'Protect your account and meet the requirements for withdrawals.',
        'stats' => [
            ['label' => 'Two-Factor Auth', 'value' => $twoFactorOn ? 'Enabled' : 'Off'],
            ['label' => 'Withdrawals', 'value' => $requirementsMet ? 'Unlocked' : 'Locked'],
            ['label' => 'Account', 'value' => ucfirst($kycStatus)],
        ],
    ])

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-primary"></i>
                    <h5 class="mb-0">Two-Factor Authentication (2FA)</h5>
                </div>
                <div class="card-body">
                    @if ($twoFactorOn)
                        <div class="status-strip rounded bg-success-subtle border border-success-subtle text-success mb-4">
                            <i class="fa-solid fa-shield-halved"></i>
                            <div>
                                <strong class="text-body d-block">2FA is enabled</strong>
                                <span class="small text-muted">Your account is protected by a time-based one-time password (TOTP).</span>
                            </div>
                        </div>

                        <div class="panel-intro mb-3">
                            <i class="fa-solid fa-circle-info"></i>
                            <div class="small">
                                To disable 2FA, enter your current <strong class="text-body">6-digit code</strong> below. You'll need 2FA enabled again before requesting withdrawals.
                            </div>
                        </div>

                        <form wire:submit="disable" style="max-width: 28rem;">
                            <label for="disableCode" class="form-label small fw-semibold">Current code</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                                <input wire:model="disableCode" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" id="disableCode" class="form-control">
                                <button class="btn btn-outline-danger" type="submit" wire:loading.attr="disabled">
                                    <i class="fa-solid fa-lock-open me-1"></i> Disable
                                </button>
                            </div>
                            @error('disableCode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </form>
                    @else
                        @unless ($showSetup)
                            <div class="panel-intro mb-3">
                                <i class="fa-solid fa-circle-info"></i>
                                <div class="small">
                                    Two-factor authentication adds an extra layer of security to your account. It is
                                    <strong class="text-body">required before you can request a withdrawal</strong>.
                                </div>
                            </div>
                            <button wire:click="beginSetup" class="btn btn-primary invest-cta">
                                <i class="fa-solid fa-shield-halved me-1"></i> Enable 2FA
                            </button>
                        @endunless

                        @if ($showSetup)
                            <div class="row g-4 align-items-start">
                                <div class="col-12 col-md-5 text-center">
                                    <div class="bg-white border rounded-3 p-3 d-inline-block shadow-sm">
                                        {!! app(\App\Support\TwoFactorAuth::class)->qrCodeSvg($user, $pendingSecret) !!}
                                    </div>
                                    <p class="text-muted small mt-3 mb-0">
                                        <i class="fa-solid fa-qrcode me-1"></i>Scan with Google Authenticator or any TOTP app.
                                    </p>
                                </div>
                                <div class="col-12 col-md-7">
                                    <label class="form-label small fw-semibold">Manual setup key</label>
                                    <div class="input-group mb-2">
                                        <input type="text" readonly value="{{ $pendingSecret }}" class="form-control font-monospace" aria-label="Manual setup key">
                                        <button class="btn btn-outline-secondary" type="button" data-copy="{{ $pendingSecret }}" title="Copy key">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    </div>
                                    <p class="form-text mt-0 mb-3">Can't scan the code? Enter this key manually into your authenticator app.</p>

                                    <form wire:submit="confirmSetup" style="max-width: 28rem;">
                                        <label for="verificationCode" class="form-label small fw-semibold">Enter the 6-digit code</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                                            <input wire:model="verificationCode" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" id="verificationCode" class="form-control">
                                            <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                                                <i class="fa-solid fa-check me-1"></i> Confirm
                                            </button>
                                        </div>
                                        @error('verificationCode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        <div class="form-text">Codes refresh every 30 seconds. Use the latest code shown in your app.</div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-up text-primary"></i>
                    <h5 class="mb-0">Withdrawal Requirements</h5>
                </div>
                <div class="card-body">
                    <div class="status-strip rounded {{ $requirementsMet ? 'bg-success-subtle border border-success-subtle text-success' : 'bg-warning-subtle border border-warning-subtle text-warning' }} mb-3">
                        <i class="fa-solid {{ $requirementsMet ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                        <div>
                            <strong class="text-body d-block">{{ $requirementsMet ? "You're eligible to withdraw" : 'Requirements not met yet' }}</strong>
                            <span class="small text-muted">{{ $requirementsMet ? 'All checks are complete.' : 'Complete every check below to unlock withdrawals.' }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-muted">Progress</span>
                        <span class="fw-semibold">{{ $doneCount }}/{{ count($checks) }}</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar {{ $requirementsMet ? 'bg-success' : 'bg-primary' }}" style="width: {{ ($doneCount / max(count($checks), 1)) * 100 }}%"></div>
                    </div>

                    <div class="pt-1">
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

                    <div class="d-grid gap-2 mt-4">
                        @if ($requirementsMet)
                            <a href="{{ route('withdrawals.index') }}" class="btn btn-primary invest-cta" wire:navigate>
                                <i class="fa-solid fa-circle-up me-1"></i> Request a Withdrawal
                            </a>
                        @else
                            <a href="{{ route('profile') }}" class="btn btn-outline-primary" wire:navigate>
                                <i class="fa-solid fa-id-card me-1"></i> Complete Verification
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
