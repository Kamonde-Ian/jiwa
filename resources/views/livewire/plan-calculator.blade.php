<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="text-primary fs-2 mb-2"><i class="fa-solid fa-calculator"></i></div>
            <h5 class="fw-bold text-body mb-1">Estimate your earnings</h5>
            <p class="text-muted small mb-0">Pick a plan and amount to see your projected returns.</p>
        </div>

        <div class="row g-4 align-items-end">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-body" for="calc-plan">Investment plan</label>
                <select wire:model.live="selectedPlanId" id="calc-plan" class="form-select">
                    <option value="">Select a plan</option>
                    @foreach ($this->plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->duration_days }} days</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold text-body" for="calc-amount">Investment amount (USD)</label>
                <input wire:model.live="amount" type="number" min="0" step="0.01" id="calc-amount" class="form-control" placeholder="e.g. 500">
            </div>
        </div>

        @if ($this->selectedPlan)
            <div class="row g-4 mt-4">
                <div class="col-6 col-md-3 text-center">
                    <div class="small text-muted">Daily return</div>
                    <div class="fs-5 fw-bold text-body">${{ number_format($this->figures['daily'], 2) }}</div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="small text-muted">Monthly (30d)</div>
                    <div class="fs-5 fw-bold text-body">${{ number_format($this->figures['monthly'], 2) }}</div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="small text-muted">Profit at maturity</div>
                    <div class="fs-5 fw-bold text-success">${{ number_format($this->figures['duration'], 2) }}</div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="small text-muted">Total payout</div>
                    <div class="fs-5 fw-bold text-body">${{ number_format($this->figures['total'], 2) }}</div>
                </div>
            </div>

            <div class="mt-4 p-3 rounded-3" style="background:#f7f7fa">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-muted">Plan</td>
                                <td class="text-end fw-semibold">{{ $this->selectedPlan->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Daily rate</td>
                                <td class="text-end fw-semibold">{{ number_format($this->selectedPlan->daily_rate * 100, 2) }}%</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Annualized rate</td>
                                <td class="text-end fw-semibold">{{ number_format($this->figures['annualized'] * 100, 0) }}%</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Duration</td>
                                <td class="text-end fw-semibold">{{ $this->selectedPlan->duration_days }} days</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Principal returned</td>
                                <td class="text-end fw-semibold">${{ number_format($this->figures['amount'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <a href="{{ route('register') }}" class="btn btn-primary px-4">Start investing with ${{ number_format(max(0, $this->figures['amount']), 0) }}</a>
            </div>
        @else
            <div class="alert alert-secondary rounded-3 mt-4 mb-0">
                <i class="fa-solid fa-circle-info me-2"></i>Select a plan and enter an amount to see a full earnings breakdown.
            </div>
        @endif
    </div>
</div>