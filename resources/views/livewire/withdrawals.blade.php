<div>
    @if (session()->has('withdrawal_requested'))
        <div class="alert alert-success" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            Withdrawal submitted! Your funds have been reserved. Review the details and payout.
        </div>
    @endif
    @if (session()->has('withdrawal_cancelled'))
        <div class="alert alert-info" role="alert">
            <i class="fa-solid fa-rotate-left me-2"></i>
            Withdrawal cancelled and funds returned to your wallet.
        </div>
    @endif

    @include('livewire.partials.page-hero', [
        'title' => 'Withdrawals',
        'subtitle' => 'Withdraw your earnings and referral income to any supported network.',
        'stats' => [
            ['label' => 'Total Withdrawn', 'value' => '$' . number_format($stats['withdrawn'], 2)],
            ['label' => 'Pending Review', 'value' => $stats['pending']],
            ['label' => 'Withdrawable', 'value' => '$' . number_format($stats['withdrawable'], 2)],
        ],
    ])

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5>Request a Withdrawal</h5>
                </div>
                <div class="card-body">
                    <div class="panel-intro mb-4">
                        <i class="fa-solid fa-circle-info"></i>
                        <div class="small">
                            Principal wallet is locked while you have an active investment. Withdrawing from the
                            principal wallet requires 2FA and a verified identity.
                        </div>
                    </div>
                    <form wire:submit="request">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="wallet_type">Source Wallet</label>
                            <select id="wallet_type" wire:model.live="wallet_type" class="form-select">
                                @foreach ($wallets as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="amount">Amount (USD)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="{{ config('jiwa.min_withdrawal') }}"
                                        id="amount" wire:model="amount" class="form-control">
                                </div>
                                <div class="form-text">
                                    Minimum ${{ number_format(config('jiwa.min_withdrawal'), 2) }}.
                                </div>
                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="network">Network</label>
                                <select id="network" wire:model.live="network" class="form-select">
                                    @foreach ($networks as $netKey => $netConfig)
                                        <option value="{{ $netKey }}">{{ $netConfig['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label small fw-semibold" for="destination_address">Destination Address</label>
                            <input type="text" id="destination_address" wire:model="destination_address"
                                class="form-control font-monospace" placeholder="Your {{ $networks[$this->network]['name'] }} address">
                            @error('destination_address')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="otp">Two-Factor Authentication Code</label>
                            <input type="text" id="otp" inputmode="numeric" maxlength="6" wire:model="otp"
                                class="form-control" placeholder="6-digit code" style="max-width: 14rem;">
                            <div class="form-text">2FA is mandatory for all withdrawals.</div>
                            @error('otp')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg invest-cta">
                            <i class="fa-solid fa-arrow-up me-2"></i>Submit Withdrawal
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5>Withdrawal History</h5>
                </div>
                <div class="card-body">
                    @if ($withdrawals->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-circle-up"></i>
                            <p class="mb-0">No withdrawals yet. Your payouts will appear here.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Wallet</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($withdrawals as $withdrawal)
                                        <tr>
                                            <td><span class="text-capitalize">{{ $withdrawal->wallet_type }}</span></td>
                                            <td>${{ number_format((float) $withdrawal->amount, 2) }}</td>
                                            <td>
                                                @if ($withdrawal->status === 'completed')
                                                    <span class="badge badge-soft-success">Completed</span>
                                                @elseif ($withdrawal->status === 'approved')
                                                    <span class="badge badge-soft-info">Approved</span>
                                                @elseif ($withdrawal->status === 'pending_review')
                                                    <span class="badge badge-soft-warning">Pending</span>
                                                @elseif ($withdrawal->status === 'rejected' || $withdrawal->status === 'cancelled')
                                                    <span class="badge badge-soft-danger">{{ ucfirst($withdrawal->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $withdrawal->created_at->format('M j, Y') }}</td>
                                            <td>
                                                @if ($withdrawal->status === 'pending_review')
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        wire:click="cancel({{ $withdrawal->id }})"
                                                        wire:confirm="Cancel this withdrawal and return the funds?">
                                                        Cancel
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $withdrawals->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
