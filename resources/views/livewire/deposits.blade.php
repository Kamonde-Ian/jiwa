<div>
    @if (session()->has('deposit_requested'))
        <div class="alert alert-success d-flex align-items-center gap-3" role="alert">
            <i class="fa-solid fa-circle-check fa-xl"></i>
            <div>
                <strong>Deposit submitted!</strong>
                An admin will verify your transaction and credit your principal wallet.
            </div>
        </div>
    @endif

    @include('livewire.partials.page-hero', [
        'title' => 'Deposits',
        'subtitle' => 'Fund your principal wallet by submitting a deposit for review.',
        'stats' => [
            ['label' => 'Total Deposited', 'value' => '$' . number_format($stats['deposited'], 2)],
            ['label' => 'Confirmed', 'value' => $stats['confirmed']],
            ['label' => 'Pending Review', 'value' => $stats['pending']],
        ],
    ])

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5>Request a Deposit</h5>
                </div>
                <div class="card-body">
                    @php $active = $networks[$network] ?? $networks->first(); @endphp
                    <div class="panel-intro mb-4">
                        <i class="fa-solid fa-circle-info"></i>
                        <div class="small">
                            Send funds to the address below on the selected network, then submit the transaction hash.
                            Deposits are credited to your principal wallet after admin confirmation.
                        </div>
                    </div>
                    <form wire:submit="request">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="network">Network</label>
                            <select id="network" wire:model.live="network" class="form-select">
                                @foreach ($networks as $netKey => $netConfig)
                                    <option value="{{ $netKey }}">{{ $netConfig['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Deposit Address</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" readonly
                                    value="{{ $active['deposit_address'] ?: 'Configured by admin — check announcements' }}">
                                <button type="button" class="btn btn-outline-secondary" x-on:click="
                                    navigator.clipboard.writeText($el.closest('.input-group').querySelector('input').value);
                                    $el.querySelector('i').classList.toggle('fa-copy');
                                    $el.querySelector('i').classList.toggle('fa-check');
                                "><i class="fa-solid fa-copy"></i></button>
                            </div>
                            <div class="form-text">
                                Send {{ $active['currency'] }} to this address on {{ $active['name'] }} only. Using a
                                different network will result in loss of funds.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="tx_hash">Transaction Hash</label>
                                <input type="text" id="tx_hash" wire:model="tx_hash" class="form-control"
                                    placeholder="Paste your TXID">
                                @error('tx_hash')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="amount_usd">Amount (USD value)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0.01" id="amount_usd"
                                        wire:model="amount_usd" class="form-control">
                                </div>
                                @error('amount_usd')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg invest-cta mt-4">
                            <i class="fa-solid fa-arrow-down me-2"></i>Submit Deposit
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5>Deposit History</h5>
                </div>
                <div class="card-body">
                    @if ($deposits->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-circle-down"></i>
                            <p class="mb-0">No deposits yet. Submit your first deposit to get started.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Network</th>
                                        <th>Tx Hash</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($deposits as $deposit)
                                        <tr>
                                            <td><span class="text-uppercase">{{ $deposit->network }}</span></td>
                                            <td>
                                                <code class="small">{{ \Illuminate\Support\Str::limit($deposit->tx_hash, 18) }}</code>
                                            </td>
                                            <td>${{ number_format((float) $deposit->amount_usd, 2) }}</td>
                                            <td>
                                                @if ($deposit->status === 'confirmed')
                                                    <span class="badge badge-soft-success">Confirmed</span>
                                                @elseif ($deposit->status === 'pending')
                                                    <span class="badge badge-soft-warning">Pending</span>
                                                @else
                                                    <span class="badge badge-soft-danger">{{ ucfirst($deposit->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $deposit->created_at->format('M j, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $deposits->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
