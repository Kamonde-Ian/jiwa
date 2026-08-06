<div>
    @include('livewire.partials.page-hero', [
        'title' => 'Dashboard',
        'subtitle' => 'Welcome back, ' . auth()->user()->name . ' — here is your portfolio at a glance.',
        'stats' => [
            ['label' => 'Total Balance', 'value' => '$' . number_format($totalBalance, 2)],
            ['label' => 'Active Investments', 'value' => $activeInvestments],
            ['label' => 'Total Earnings', 'value' => '$' . number_format($totalEarnings, 2)],
            ['label' => 'Referral Income', 'value' => '$' . number_format($referralIncome, 2)],
        ],
    ])

    <div class="row g-4 mt-1">
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Growth Overview</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    @if (count($chart['values']) > 0)
                        @php
                            $growthConfig = [
                                'chart' => ['type' => 'area', 'height' => 300, 'toolbar' => ['show' => false]],
                                'series' => [['name' => 'Earnings', 'data' => $chart['values']]],
                                'xaxis' => ['categories' => $chart['labels'], 'labels' => ['style' => ['colors' => '#566a7f']]],
                                'stroke' => ['curve' => 'smooth', 'width' => 2.5],
                                'colors' => ['#696cff'],
                                'fill' => ['gradient' => ['opacityFrom' => 0.25, 'opacityTo' => 0]],
                                'dataLabels' => ['enabled' => false],
                                'grid' => ['borderColor' => '#eceef1'],
                            ];
                        @endphp
                        <div data-chart='@json($growthConfig)' class="chart-fill"></div>
                    @else
                        <div class="empty-state">
                            <i class="fa-regular fa-chart-line"></i>
                            <p class="mb-0">Your earnings chart will appear once interest is credited.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Portfolio</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    @if (count($portfolioChart['values']) > 0 && $totalBalance > 0)
                        @php
                            $portfolioConfig = [
                                'chart' => ['type' => 'donut', 'height' => 240, 'toolbar' => ['show' => false]],
                                'series' => $portfolioChart['values'],
                                'labels' => $portfolioChart['labels'],
                                'colors' => ['#696cff', '#71dd37', '#ffab00'],
                                'stroke' => ['width' => 0],
                                'dataLabels' => ['enabled' => false],
                                'legend' => ['position' => 'bottom', 'fontSize' => '13px'],
                                'plotOptions' => ['pie' => ['donut' => ['labels' => [
                                    'show' => true,
                                    'name' => ['fontSize' => '14px', 'offsetY' => 0],
                                    'value' => ['show' => true, 'fontSize' => '20px', 'fontWeight' => '600', 'offsetY' => 4],
                                'total' => ['show' => true, 'label' => 'Balance', 'fontSize' => '13px'],
                            ]]]],
                            ];
                        @endphp
                        <div data-chart='@json($portfolioConfig)' class="chart-fill"></div>
                    @else
                        <div class="empty-state">
                            <i class="fa-solid fa-chart-pie"></i>
                            <p class="mb-0">No funds yet. Add a deposit to see your portfolio mix.</p>
                        </div>
                    @endif

                    @foreach ($portfolio as $item)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                            <div class="d-flex align-items-center gap-2">
                                @if ($item['type'] === 'principal')
                                    <div class="avatar bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-vault"></i></div>
                                @elseif ($item['type'] === 'earnings')
                                    <div class="avatar bg-info bg-opacity-10 text-info"><i class="fa-solid fa-coins"></i></div>
                                @else
                                    <div class="avatar bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-user-group"></i></div>
                                @endif
                                <span class="text-capitalize">{{ $item['type'] }}</span>
                            </div>
                            <span class="fw-semibold">${{ number_format($item['balance'], 2) }}</span>
                        </div>
                    @endforeach

                    <div class="d-flex align-items-center justify-content-between pt-3">
                        <span class="text-muted">Total</span>
                        <span class="fw-bold fs-6">${{ number_format($totalBalance, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity</h5>
                    <a href="{{ route('statements.index') }}" class="btn btn-sm btn-outline-primary" wire:navigate>View Statements</a>
                </div>
                <div class="card-body p-0">
                    @if ($recentTransactions->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <p class="mb-0">No activity yet. Make your first deposit to get started.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Wallet</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentTransactions as $tx)
                                        <tr>
                                            <td class="text-muted">{{ $tx->created_at->format('M j, Y g:i A') }}</td>
                                            <td><span class="badge badge-soft-secondary text-capitalize">{{ $tx->wallet_type }}</span></td>
                                            <td>{{ $tx->description }}</td>
                                            <td class="text-end {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                                {{ $tx->type === 'credit' ? '+' : '-' }}${{ number_format((float) $tx->amount, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
