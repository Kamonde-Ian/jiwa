<div>
    @include('livewire.partials.page-hero', [
        'title' => 'Statements',
        'subtitle' => 'A complete record of every credit and debit across your wallets.',
        'stats' => [
            ['label' => 'Total Credits', 'value' => '$' . number_format($summary['credits'], 2)],
            ['label' => 'Total Debits', 'value' => '$' . number_format($summary['debits'], 2)],
            ['label' => 'Net Movement', 'value' => '$' . number_format($net, 2)],
        ],
    ])

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Monthly Cash Flow</h5>
        </div>
        <div class="card-body d-flex flex-column">
            @if (array_sum($cashFlow['credits']) + array_sum($cashFlow['debits']) > 0)
                @php
                    $cashFlowConfig = [
                        'chart' => ['type' => 'bar', 'height' => 300, 'toolbar' => ['show' => false]],
                        'series' => [
                            ['name' => 'Credits', 'data' => $cashFlow['credits']],
                            ['name' => 'Debits', 'data' => $cashFlow['debits']],
                        ],
                        'xaxis' => ['categories' => $cashFlow['labels'], 'labels' => ['style' => ['colors' => '#566a7f']]],
                        'colors' => ['#71dd37', '#ff3e1d'],
                        'plotOptions' => ['bar' => ['columnWidth' => '45%', 'borderRadius' => 3]],
                        'stroke' => ['show' => false],
                        'dataLabels' => ['enabled' => false],
                        'legend' => ['position' => 'top'],
                        'grid' => ['borderColor' => '#eceef1'],
                    ];
                @endphp
                <div data-chart='@json($cashFlowConfig)' class="chart-fill"></div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fa-solid fa-chart-column fa-2x mb-3 d-block"></i>
                    <p class="mb-0">Cash-flow history will appear once you make your first deposit or withdrawal.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Statement</h5>
            <select wire:model.live="walletFilter" class="form-select form-select-sm w-auto">
                <option value="all">All wallets</option>
                @foreach ($walletTypes as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="card-body p-0">
            @if ($transactions->isEmpty())
                <div class="empty-state">
                    <i class="fa-solid fa-receipt"></i>
                    <p class="mb-0">No transactions found for this wallet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Wallet</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance After</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $tx)
                                <tr>
                                    <td class="text-muted">{{ $tx->created_at->format('M j, Y g:i A') }}</td>
                                    <td>
                                        <span class="badge badge-soft-primary text-capitalize">{{ $tx->wallet->type }}</span>
                                    </td>
                                    <td>{{ $tx->description }}</td>
                                    <td>
                                        @if ($tx->type === 'credit')
                                            <span class="badge badge-soft-success">Credit</span>
                                        @else
                                            <span class="badge badge-soft-danger">Debit</span>
                                        @endif
                                    </td>
                                    <td class="text-end {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }} fw-semibold">
                                        {{ $tx->type === 'credit' ? '+' : '-' }}${{ number_format((float) $tx->amount, 2) }}
                                    </td>
                                    <td class="text-end text-muted">${{ number_format((float) $tx->balance_after, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $transactions->links() }}</div>
            @endif
        </div>
    </div>
</div>
