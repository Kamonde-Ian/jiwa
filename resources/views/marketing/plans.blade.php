@extends('layouts.marketing')

@section('title', 'Investment Plans — '.config('app.name'))

@php
    $plans = \App\Models\InvestmentPlan::where('is_active', true)->orderBy('min_amount')->get();
    $minRate = $plans->first() ? (float) $plans->min('daily_rate') : (float) config('jiwa.default_daily_rate');
    $maxRate = $plans->first() ? (float) $plans->max('daily_rate') : $minRate;
    $minAmount = $plans->first() ? (float) $plans->min('min_amount') : (float) config('jiwa.min_investment');
    $planCount = $plans->count();
    $creditHours = (int) config('jiwa.interest_credit_hours');
    $features = [
        'Daily interest credited automatically',
        'Principal returned at maturity',
        'Withdrawals anytime',
        'No hidden fees',
        '24/7 priority support',
    ];
@endphp

@section('content')
    <section class="hero-section py-5">
        <div class="container hero-inner">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow mb-4"><i class="fa-solid fa-chart-line me-1"></i> Investment Plans</span>
                    <h1 class="mt-3 mb-4">Plans built for <strong>steady, predictable growth</strong></h1>
                    <p class="lead mb-0">
                        Choose from {{ $planCount }} transparent plans — from short-term to long-term — with daily returns
                        credited automatically and your principal returned at maturity.
                    </p>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual">
                        <div class="card round-card gradient-glow">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small fw-semibold text-body">Plans at a glance</div>
                                    <span class="badge badge-soft-primary">Transparent</span>
                                </div>
                                <div class="my-3">
                                    @foreach ($plans as $plan)
                                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                                            <span class="text-muted">{{ $plan->name }} · {{ $plan->duration_days }}d</span>
                                            <span class="fw-semibold text-body">{{ number_format($plan->daily_rate * 100, 2) }}%/day</span>
                                        </div>
                                    @endforeach
                                    <div class="d-flex justify-content-between align-items-center pt-2 small">
                                        <span class="text-muted">Minimum</span>
                                        <span class="fw-semibold text-body">${{ number_format($minAmount, 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4 justify-content-center">
                @foreach ($plans as $plan)
                    @php
                        $isPopular = $plan->description === 'Most Popular';
                        $emoji = match ($plan->name) { 'Growth' => '⭐', 'Elite' => '👑', default => '' };
                        $rangeLabel = $plan->max_amount === null
                            ? '$' . number_format((float) $plan->min_amount, 0) . '+'
                            : '$' . number_format((float) $plan->min_amount, 0) . ' – $' . number_format((float) $plan->max_amount, 0);
                        $maxLabel = $plan->max_amount === null ? 'Unlimited' : '$' . number_format((float) $plan->max_amount, 0);
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card round-card lift h-100 {{ $isPopular ? 'plan-featured' : '' }}">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="text-center">
                                    <div class="badge badge-soft-primary mb-1 text-uppercase fs-6">{{ $plan->name }} {{ $emoji }}</div>
                                    <div class="text-muted small mb-3 text-uppercase" style="letter-spacing:.5px">{{ $plan->description }}</div>
                                    <div class="fs-3 fw-bold text-body mb-1">{{ number_format($plan->daily_rate * 100, 2) }}%<span class="fs-6 text-muted fw-normal"> /day</span></div>
                                    <div class="text-muted small mb-3">{{ $plan->duration_days }} days · {{ $rangeLabel }}</div>
                                    <div class="text-muted small mb-3">≈ {{ number_format($plan->daily_rate * 365 * 100, 1) }}% annualized</div>
                                </div>

                                <div class="my-2 text-start small">
                                    <div class="d-flex justify-content-between border-top py-2">
                                        <span class="text-muted">Minimum deposit</span>
                                        <span class="fw-semibold">${{ number_format($plan->min_amount, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2">
                                        <span class="text-muted">Maximum deposit</span>
                                        <span class="fw-semibold">{{ $maxLabel }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2">
                                        <span class="text-muted">Daily return (min)</span>
                                        <span class="fw-semibold text-body">${{ number_format($plan->min_amount * $plan->daily_rate, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2">
                                        <span class="text-muted">Profit at maturity (min)</span>
                                        <span class="fw-semibold text-success">${{ number_format($plan->min_amount * $plan->daily_rate * $plan->duration_days, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted">Est. total payout (min)</span>
                                        <span class="fw-semibold text-body">${{ number_format($plan->min_amount + $plan->min_amount * $plan->daily_rate * $plan->duration_days, 2) }}</span>
                                    </div>
                                </div>

                                <ul class="list-unstyled small mb-3 pt-2">
                                    @foreach ($features as $feature)
                                        <li class="mb-1"><i class="fa-solid fa-circle-check text-success me-2"></i>{{ $feature }}</li>
                                    @endforeach
                                </ul>

                                <a href="{{ route('register') }}" class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} w-100 mt-auto">Invest Now</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-5 section-alt">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-table-columns me-1"></i> Compare plans</span>
                <h2 class="section-title mb-2">Compare <strong>all plans</strong></h2>
                <p class="section-lead mb-0">Every plan includes daily interest, principal return, and no hidden fees.</p>
            </div>

            <div class="card round-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Positioning</th>
                                    <th>Investment range</th>
                                    <th>Daily rate</th>
                                    <th>Annualized</th>
                                    <th>Duration</th>
                                    <th class="text-end">Est. total payout (min)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($plans as $plan)
                                    @php
                                        $isPopular = $plan->description === 'Most Popular';
                                        $emoji = match ($plan->name) { 'Growth' => '⭐', 'Elite' => '👑', default => '' };
                                        $rangeLabel = $plan->max_amount === null
                                            ? '$' . number_format((float) $plan->min_amount, 0) . '+'
                                            : '$' . number_format((float) $plan->min_amount, 0) . ' – $' . number_format((float) $plan->max_amount, 0);
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $plan->name }} {{ $emoji }}</td>
                                        <td class="text-muted">{{ $plan->description }}</td>
                                        <td>{{ $rangeLabel }}</td>
                                        <td>{{ number_format($plan->daily_rate * 100, 2) }}%</td>
                                        <td>≈ {{ number_format($plan->daily_rate * 365 * 100, 1) }}%</td>
                                        <td>{{ $plan->duration_days }} days</td>
                                        <td class="text-end fw-semibold">${{ number_format($plan->min_amount + $plan->min_amount * $plan->daily_rate * $plan->duration_days, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <livewire:plan-calculator />
        </div>
    </section>

    <section class="py-5 section-alt">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-wallet me-1"></i> Wallets</span>
                <h2 class="section-title mb-2">Three wallets. <strong>Full control.</strong></h2>
                <p class="section-lead mb-0">Each account keeps your capital, earnings, and referral income clearly separated.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4">
                            <div class="icon-badge mb-3"><i class="fa-solid fa-vault"></i></div>
                            <h6 class="fw-semibold">Principal Wallet</h6>
                            <p class="text-muted small mb-0">Holds your invested capital, locked until your plan matures. New investments are made from this wallet.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4">
                            <div class="icon-badge mb-3"><i class="fa-solid fa-coins"></i></div>
                            <h6 class="fw-semibold">Earnings Wallet</h6>
                            <p class="text-muted small mb-0">Your daily interest accumulates here — credited every 24 hours and withdrawable any time.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4">
                            <div class="icon-badge mb-3"><i class="fa-solid fa-user-plus"></i></div>
                            <h6 class="fw-semibold">Referral Wallet</h6>
                            <p class="text-muted small mb-0">Commissions from your referrals land here, ready to withdraw whenever you like.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-band row g-4 p-4 p-md-4 mt-4 text-center">
                <div class="col-6 col-md-3">
                    <div class="stat-value stat-value--range">{{ number_format($minRate * 100, 2) }}% <span class="stat-dash">–</span> {{ number_format($maxRate * 100, 2) }}%</div>
                    <div class="small">Daily interest range</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-value">{{ $creditHours }}h</div>
                    <div class="small">Interest credit cycle</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-value">${{ number_format($minAmount, 0) }}</div>
                    <div class="small">Minimum investment</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-value">{{ $planCount }}</div>
                    <div class="small">Fixed + custom plans</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 section-alt">
        <div class="container" style="max-width:44rem">
            <div class="section-head mb-4">
                <span class="eyebrow mb-3"><i class="fa-solid fa-circle-question me-1"></i> Plan FAQ</span>
                <h2 class="section-title">Plan <strong>FAQ</strong></h2>
            </div>

            <div class="accordion" id="planFaq">
                @php
                    $planFaqs = [
                        ['q' => 'How and when is interest credited?', 'a' => 'Interest accrues daily at your plan\'s rate and is credited to your earnings wallet automatically each day. No manual claims required.'],
                        ['q' => 'What happens when my plan matures?', 'a' => 'At maturity your invested principal is released back to your principal wallet, and all accumulated profit stays in your earnings wallet — ready to reinvest or withdraw.'],
                        ['q' => 'Can I invest more than the minimum?', 'a' => 'Yes. Each plan accepts investments from its minimum up to its maximum deposit limit (the Elite plan is unlimited). Your projected returns scale proportionally with the amount invested.'],
                        ['q' => 'Can I invest in multiple plans at once?', 'a' => 'Absolutely. There is no limit on the number of active investments you can hold across different plans.'],
                        ['q' => 'What cryptocurrencies can I use to fund my plan?', 'a' => 'You can deposit Bitcoin (BTC), Ethereum (ETH), or USDT (TRC-20 / ERC-20) into your principal wallet, then invest from the wallet balance.'],
                    ];
                @endphp

                @foreach ($planFaqs as $i => $faq)
                    <div class="accordion-item mb-2 border-0 rounded-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }} fw-semibold" type="button"
                                data-bs-toggle="collapse" data-bs-target="#planFaq{{ $i }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">
                                {{ $faq['q'] }}
                            </button>
                        </h2>
                        <div id="planFaq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#planFaq">
                            <div class="accordion-body text-muted">{{ $faq['a'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container text-center">
            <div class="alert alert-warning rounded-3 text-start mx-auto" style="max-width:44rem" role="alert">
                <h6 class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Risk disclaimer</h6>
                <p class="small mb-0">
                    All investments carry risk. Projected returns are estimates and not guaranteed. Past performance does not
                    predict future results. Only invest capital you can afford to lose. {{ config('app.name') }} does not provide
                    financial advice; please consult a qualified professional before investing.
                </p>
            </div>
        </div>
    </section>
@endsection