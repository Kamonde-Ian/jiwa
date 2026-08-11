<div>
    @include('livewire.partials.page-hero', [
        'title' => 'My Investments',
        'subtitle' => 'Track your active positions, maturity dates and projected returns.',
        'stats' => [
            ['label' => 'Active', 'value' => $summary['active']],
            ['label' => 'Total Invested', 'value' => '$' . number_format($summary['total'], 2)],
            ['label' => 'Active Principal', 'value' => '$' . number_format($summary['active_total'], 2)],
            ['label' => 'Matured', 'value' => $summary['matured']],
        ],
    ])

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Allocation by Plan</h5>
        </div>
        <div class="card-body">
            @if (count($allocationChart['values']) > 0)
                <div class="row g-4">
                    <div class="col-12 col-lg-6 d-flex">
                        @php
                            $allocationConfig = [
                                'chart' => ['type' => 'donut', 'height' => 280, 'toolbar' => ['show' => false]],
                                'series' => $allocationChart['values'],
                                'labels' => $allocationChart['labels'],
                                'colors' => ['#D8A839', '#71dd37', '#986817', '#ffab00', '#ff3e1d'],
                                'stroke' => ['width' => 0],
                                'dataLabels' => ['enabled' => false],
                                'legend' => ['position' => 'bottom', 'fontSize' => '13px'],
                                'plotOptions' => ['pie' => ['donut' => ['labels' => [
                                    'show' => true,
                                    'name' => ['fontSize' => '14px', 'offsetY' => 0],
                                    'value' => ['show' => true, 'fontSize' => '20px', 'fontWeight' => '600', 'offsetY' => 4],
                                    'total' => ['show' => true, 'label' => 'Active principal', 'fontSize' => '13px'],
                                ]]]],
                            ];
                        @endphp
                        <div data-chart='@json($allocationConfig)' class="chart-fill"></div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th class="text-end">Principal</th>
                                        <th class="text-end">Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($allocationChart['labels'] as $i => $label)
                                        <tr>
                                            <td class="fw-semibold">{{ $label }}</td>
                                            <td class="text-end">${{ number_format((float) $allocationChart['values'][$i], 2) }}</td>
                                            <td class="text-end text-muted">
                                                {{ $summary['active_total'] > 0 ? number_format((float) $allocationChart['values'][$i] / $summary['active_total'] * 100, 1) : 0 }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <i class="fa-solid fa-chart-pie"></i>
                    <p class="mb-0">Your plan allocation chart will appear once you hold active investments.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Investment History</h5>
        </div>
        <div class="card-body p-0">
            @if ($investments->isEmpty())
                <div class="empty-state">
                    <i class="fa-solid fa-sack-dollar"></i>
                    <p class="mb-0">
                        No investments yet. <a href="{{ route('invest') }}" wire:navigate class="fw-semibold">Fund your wallet and invest</a>.
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Principal</th>
                                <th>Daily Rate</th>
                                <th>Started</th>
                                <th>Matures</th>
                                <th>Days Left</th>
                                <th>Status</th>
                                <th class="text-end">Projected Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($investments as $investment)
                                <tr>
                                    <td class="fw-semibold">{{ $investment->plan?->name ?? '—' }}</td>
                                    <td>${{ number_format((float) $investment->principal_amount, 2) }}</td>
                                    <td>{{ number_format((float) $investment->daily_rate_snapshot * 100, 2) }}%</td>
                                    <td class="text-muted">{{ $investment->starts_at->format('M j, Y') }}</td>
                                    <td class="text-muted">{{ $investment->matures_at->format('M j, Y') }}</td>
                                    <td>
                                        @if ($investment->status === 'active')
                                            <span class="text-muted">{{ max(0, $investment->matures_at->diffInDays(now())) }}d</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($investment->status === 'active')
                                            <span class="badge badge-soft-success">Active</span>
                                        @elseif ($investment->status === 'matured')
                                            <span class="badge badge-soft-info">Matured</span>
                                        @else
                                            <span class="badge badge-soft-secondary">Cancelled</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">
                                        ${{ number_format((float) $investment->principal_amount + (float) $investment->principal_amount * (float) $investment->daily_rate_snapshot * max(1, (int) $investment->plan?->duration_days), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $investments->links() }}</div>
            @endif
        </div>
    </div>
</div>
