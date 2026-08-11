<div>
    @if (session()->has('invested'))
        @php $done = session('invested'); @endphp
        <div class="alert alert-success d-flex align-items-center gap-3" role="alert">
            <i class="fa-solid fa-circle-check fa-xl"></i>
            <div>
                <strong>Investment placed!</strong>
                You committed ${{ number_format($done['amount'], 2) }} to {{ $done['plan'] }} at
                {{ number_format($done['rate'] * 100, 2) }}% daily. It matures on {{ $done['matures_at'] }}.
            </div>
        </div>
    @endif

    @php
        $selectedPlan = $plans->firstWhere('id', $selectedPlanId);
        $amt = (float) ($amount ?? 0);
        $dailyReturn = $selectedPlan && $amt ? round($amt * (float) $selectedPlan->daily_rate, 2) : 0;
        $projectedEarnings = $selectedPlan && $amt ? round($dailyReturn * (int) $selectedPlan->duration_days, 2) : 0;
        $projectedTotal = round($amt + $projectedEarnings, 2);
        $heroRate = ($selectedPlan ?? $plans->first())?->daily_rate * 100 ?? 0;
        $progressPct = $principal > 0 ? min(100, $amt / $principal * 100) : 0;
    @endphp

    @include('livewire.partials.page-hero', [
        'title' => 'Start Investing',
        'subtitle' => 'Pick a plan, choose your amount and let your capital grow every day.',
        'stats' => [
            ['label' => 'Available', 'value' => '$' . number_format($principal, 2)],
            ['label' => 'Daily Rate', 'value' => number_format($heroRate, 2) . '%'],
            ['label' => 'Active', 'value' => $activeInvestments],
        ],
    ])

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Choose a Plan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        @foreach ($plans as $plan)
                            @php
                                $isSelected = $selectedPlanId === $plan->id;
                                $isPopular = $plan->description === 'Most Popular';
                                $planEmoji = match ($plan->name) { 'Growth' => '⭐', 'Elite' => '👑', default => '' };
                                $planMax = $plan->max_amount === null ? 'Unlimited' : '$' . number_format((float) $plan->max_amount, 0);
                            @endphp
                            <div class="col-12 col-md-6">
                                <label class="plan-card {{ $isSelected ? 'plan-selected' : '' }}">
                                    <input type="radio" name="plan" value="{{ $plan->id }}"
                                        wire:model.live="selectedPlanId" class="d-none plan-radio">
                                    <div class="card h-100">
                                        <div class="plan-top"></div>
                                        @if ($isPopular)
                                            <span class="plan-badge"><i class="fa-solid fa-star me-1"></i>Most Popular</span>
                                        @endif
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h6 class="fw-semibold mb-1">
                                                        {{ $plan->name }} {{ $planEmoji }}
                                                        @if ($isSelected)
                                                            <i class="fa-solid fa-circle-check text-primary ms-1"></i>
                                                        @endif
                                                    </h6>
                                                    <span class="text-muted small">
                                                        <i class="fa-regular fa-calendar me-1"></i>{{ $plan->duration_days }} days
                                                    </span>
                                                </div>
                                                <div class="text-end">
                                                    <div class="plan-rate">{{ number_format($plan->daily_rate * 100, 2) }}%</div>
                                                    <small class="text-muted">daily</small>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <span class="badge badge-soft-primary">${{ number_format((float) $plan->min_amount, 0) }} – {{ $planMax }}</span>
                                            </div>
                                            <ul class="list-unstyled mb-0">
                                                <li class="plan-feature"><i class="fa-solid fa-circle-check"></i>Daily returns credited every 24h</li>
                                                <li class="plan-feature"><i class="fa-solid fa-lock"></i>Principal locked until maturity</li>
                                                <li class="plan-feature"><i class="fa-solid fa-wallet"></i>Earnings withdrawable anytime</li>
                                            </ul>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    @error('selectedPlanId')
                        <p class="text-danger small">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5>Make an Investment</h5>
                </div>
                <div class="card-body">
                    @if ($selectedPlan)
                        <div class="selected-plan-summary mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted text-uppercase fw-semibold">Selected plan</small>
                                    <div class="fw-semibold">{{ $selectedPlan->name }}</div>
                                </div>
                                <span class="badge badge-soft-primary">{{ number_format($selectedPlan->daily_rate * 100, 2) }}% / day</span>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light border mb-3 py-2 small">
                            <i class="fa-regular fa-circle-question me-1"></i>Select a plan to see your projected returns.
                        </div>
                    @endif

                    <form wire:submit="invest">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="amount">Investment Amount (USD)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="{{ $selectedPlan?->min_amount ?? config('jiwa.min_investment') }}"
                                        id="amount" wire:model.live="amount" class="form-control"
                                        placeholder="Minimum ${{ number_format((float) ($selectedPlan?->min_amount ?? config('jiwa.min_investment')), 0) }}">
                                </div>
                                @if ($selectedPlan)
                                    <div class="text-muted small mt-1">
                                        This plan accepts ${{ number_format((float) $selectedPlan->min_amount, 0) }} –
                                        {{ $selectedPlan->max_amount === null ? 'Unlimited' : '$' . number_format((float) $selectedPlan->max_amount, 0) }}.
                                    </div>
                                @endif
                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary quick-chip flex-fill"
                                wire:click="setAmountPercent(25)">25%</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-chip flex-fill"
                                wire:click="setAmountPercent(50)">50%</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-chip flex-fill"
                                wire:click="setAmountPercent(75)">75%</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-chip flex-fill"
                                wire:click="setAmountPercent(100)">Max</button>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Available principal</span>
                                <strong>${{ number_format($principal, 2) }}</strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" style="width: {{ $progressPct }}%; background: var(--sneat-primary);"></div>
                            </div>
                        </div>

                        <div class="projection-box mb-4">
                            <div class="row g-3 text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Daily Return</small>
                                    <div class="projection-value">${{ number_format($dailyReturn, 2) }}</div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Matures</small>
                                    <div class="projection-value" style="font-size: 0.95rem;">
                                        {{ $selectedPlan ? now()->addDays($selectedPlan->duration_days)->format('M j, Y') : '—' }}
                                    </div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block mb-1">Projected Total</small>
                                    <div class="projection-value">${{ number_format($projectedTotal, 2) }}</div>
                                </div>
                            </div>
                            @if ($selectedPlan && $amt)
                                <div class="text-muted small text-center mt-3">
                                    <i class="fa-solid fa-coins me-1"></i>
                                    Estimated profit of <strong>${{ number_format($projectedEarnings, 2) }}</strong>
                                    over {{ $selectedPlan->duration_days }} days at
                                    {{ number_format($selectedPlan->daily_rate * 100, 2) }}% daily.
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg invest-cta"
                            @if (!($selectedPlanId && $amount)) disabled @endif>
                            <i class="fa-solid fa-rocket me-2"></i>Invest Now
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>My Investments</h5>
                </div>
                <div class="card-body">
                    @if ($investments->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-sack-dollar"></i>
                            <p class="mb-0">No investments yet. Fund your principal wallet to begin.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Matures</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($investments as $investment)
                                        <tr>
                                            <td>{{ $investment->plan?->name ?? '—' }}</td>
                                            <td>${{ number_format((float) $investment->principal_amount, 2) }}</td>
                                            <td>
                                                @if ($investment->status === 'active')
                                                    <span class="badge badge-soft-success">Active</span>
                                                @elseif ($investment->status === 'matured')
                                                    <span class="badge badge-soft-info">Matured</span>
                                                @else
                                                    <span class="badge badge-soft-secondary">Cancelled</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $investment->matures_at->format('M j, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $investments->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
