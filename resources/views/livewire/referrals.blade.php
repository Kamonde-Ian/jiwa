<div>
    @include('livewire.partials.page-hero', [
        'title' => 'Referrals',
        'subtitle' => 'Share your link and earn ' . number_format($commissionRate * 100, 1) . '% commission on every investment made by people you refer.',
        'stats' => [
            ['label' => 'Referral Balance', 'value' => '$' . number_format($stats['balance'], 2)],
            ['label' => 'Commissions Earned', 'value' => '$' . number_format($stats['earned'], 2)],
            ['label' => 'Referrals', 'value' => $stats['count']],
        ],
    ])

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5>Your Referral Link</h5>
                </div>
                <div class="card-body">
                    @if ($qualified)
                        <div class="alert alert-success d-flex align-items-center gap-3 py-2" role="alert">
                            <i class="fa-solid fa-unlock"></i>
                            <span>Your referral link is unlocked. Share it to start earning commissions.</span>
                        </div>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" readonly value="{{ $referralLink }}" id="ref-link">
                            <button type="button" class="btn btn-primary" x-on:click="
                                navigator.clipboard.writeText($el.previousElementSibling.value);
                                $el.querySelector('i').classList.toggle('fa-copy');
                                $el.querySelector('i').classList.toggle('fa-check');
                            "><i class="fa-solid fa-copy"></i> Copy</button>
                        </div>
                        <div class="form-text">
                            Earn <strong>{{ number_format($commissionRate * 100, 1) }}%</strong> of every investment
                            made by someone who registers with your link.
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-center gap-3" role="alert">
                            <i class="fa-solid fa-lock fa-xl"></i>
                            <div>
                                <strong>Your referral link is locked.</strong>
                                Hold an active investment of at least
                                ${{ number_format($qualificationMinimum, 2) }} to unlock it and start earning.
                            </div>
                        </div>
                        <div class="form-text">
                            Once unlocked you will earn <strong>{{ number_format($commissionRate * 100, 1) }}%</strong>
                            of every investment made by people you refer.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>Commission History</h5>
                </div>
                <div class="card-body">
                    @if ($earnings->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-users"></i>
                            <p class="mb-0">No commissions yet. Share your link and grow your earnings.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Referred User</th>
                                        <th>Investment</th>
                                        <th>Commission</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($earnings as $earning)
                                        <tr>
                                            <td>{{ $earning->referredUser?->name ?? '—' }}</td>
                                            <td>${{ number_format((float) $earning->investment?->principal_amount, 2) }}</td>
                                            <td class="text-success">+${{ number_format((float) $earning->amount, 2) }}</td>
                                            <td class="text-muted">{{ $earning->created_at->format('M j, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $earnings->links() }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5>Commission Earnings</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    @if (array_sum($commissionChart['values']) > 0)
                        @php
                            $commissionConfig = [
                                'chart' => ['type' => 'bar', 'height' => 240, 'toolbar' => ['show' => false]],
                                'series' => [['name' => 'Commissions', 'data' => $commissionChart['values']]],
                                'xaxis' => ['categories' => $commissionChart['labels'], 'labels' => ['style' => ['colors' => '#566a7f']]],
                                'colors' => ['#D8A839'],
                                'plotOptions' => ['bar' => ['columnWidth' => '50%', 'borderRadius' => 3]],
                                'stroke' => ['show' => false],
                                'dataLabels' => ['enabled' => false],
                                'grid' => ['borderColor' => '#eceef1'],
                            ];
                        @endphp
                        <div data-chart='@json($commissionConfig)' class="chart-fill"></div>
                    @else
                        <div class="empty-state">
                            <i class="fa-solid fa-user-plus"></i>
                            <p class="mb-0">Your commission chart will appear once referred members start investing.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>My Referrals</h5>
                </div>
                <div class="card-body">
                    @php $direct = auth()->user()->referrals()->latest('id')->get(); @endphp
                    @if ($direct->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-user-plus"></i>
                            <p class="mb-0">No referrals yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($direct as $ref)
                                        <tr>
                                            <td>{{ $ref->name }}</td>
                                            <td class="text-muted">{{ $ref->email }}</td>
                                            <td class="text-muted">{{ $ref->created_at->format('M j, Y') }}</td>
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
