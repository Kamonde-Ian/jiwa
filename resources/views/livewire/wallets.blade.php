<div>
    @php
        $totalBalance = $wallets->sum(fn ($w) => (float) $w->balance);
        $withdrawable = (float) $wallets->get('earnings')->balance + (float) $wallets->get('referral')->balance;
        $locked = (float) $wallets->get('principal')->balance;
    @endphp

    @include('livewire.partials.page-hero', [
        'title' => 'My Wallets',
        'subtitle' => 'Manage your funds across principal, earnings and referral wallets.',
        'stats' => [
            ['label' => 'Total Balance', 'value' => '$' . number_format($totalBalance, 2)],
            ['label' => 'Withdrawable', 'value' => '$' . number_format($withdrawable, 2)],
            ['label' => 'Locked', 'value' => '$' . number_format($locked, 2)],
        ],
    ])

    <div class="row g-4">
        @foreach ([
            ['principal', 'Principal Wallet', 'fa-vault', 'bg-primary bg-opacity-10 text-primary', 'Locked until investment maturity'],
            ['earnings', 'Earnings Wallet', 'fa-coins', 'bg-success bg-opacity-10 text-success', 'Withdrawable anytime'],
            ['referral', 'Referral Wallet', 'fa-user-group', 'bg-warning bg-opacity-10 text-warning', 'Withdrawable anytime'],
        ] as [$type, $label, $icon, $style, $hint])
            @php $wallet = $wallets->get($type); @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <small class="text-muted text-uppercase fw-semibold">{{ $label }}</small>
                            </div>
                            <div class="avatar {{ $style }}">
                                <i class="fa-solid {{ $icon }}"></i>
                            </div>
                        </div>
                        <h4 class="mb-1">${{ number_format((float) $wallet->balance, 2) }}</h4>
                        <small class="text-muted">{{ $hint }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Recent Transactions</h5>
                </div>
                <div class="card-body">
                    @if ($transactions->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-wallet"></i>
                            <p class="mb-0">No transactions yet. Your ledger is empty.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Wallet</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Balance</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $tx)
                                        <tr>
                                            <td><span class="text-capitalize">{{ $tx->wallet_type }}</span></td>
                                            <td>
                                                @if ($tx->type === 'credit')
                                                    <span class="badge badge-soft-success">Credit</span>
                                                @else
                                                    <span class="badge badge-soft-danger">Debit</span>
                                                @endif
                                            </td>
                                            <td class="{{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                                {{ $tx->type === 'credit' ? '+' : '-' }}${{ number_format((float) $tx->amount, 2) }}
                                            </td>
                                            <td>${{ number_format((float) $tx->balance_after, 2) }}</td>
                                            <td class="text-muted">{{ $tx->created_at->format('M j, Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Earnings Growth</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    @if (count($chart['values']) > 0)
                        @php
                            $walletChartConfig = [
                                'chart' => ['type' => 'area', 'toolbar' => ['show' => false], 'height' => 320],
                                'series' => [['name' => 'Earnings Balance', 'data' => $chart['values']]],
                                'xaxis' => ['categories' => $chart['labels'], 'labels' => ['style' => ['colors' => '#566a7f']]],
                                'colors' => ['#D8A839'],
                                'stroke' => ['curve' => 'smooth'],
                                'dataLabels' => ['enabled' => false],
                                'fill' => ['opacity' => 0.2],
                                'grid' => ['borderColor' => '#eceef1'],
                            ];
                        @endphp
                        <div data-chart='@json($walletChartConfig)' class="chart-fill"></div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fa-regular fa-chart-line fa-2x mb-3 d-block"></i>
                            <p class="mb-0">Your earnings chart will appear once interest is credited.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
