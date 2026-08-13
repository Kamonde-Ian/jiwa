@extends('layouts.marketing')

@section('title', 'About — '.config('app.name'))

@section('content')
    @php
        $planRates = \App\Models\InvestmentPlan::where('is_active', true)->orderBy('daily_rate')->pluck('daily_rate');
        $ratesMin = $planRates->first() ?? config('jiwa.default_daily_rate');
        $ratesMax = $planRates->last() ?? config('jiwa.default_daily_rate');
        $supportedCoins = \Illuminate\Support\Collection::make(config('jiwa.networks'))->pluck('currency')->unique()->count();
    @endphp

    <section class="hero-section py-5">
        <div class="container hero-inner">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow mb-4"><i class="fa-solid fa-building-columns me-1"></i> About us</span>
                    <h1 class="mt-3 mb-4">A crypto platform built for <strong>steady, predictable growth</strong></h1>
                    <p class="lead mb-0">
                        {{ config('app.name') }} pairs predictable daily returns with bank-grade security and full
                        transparency — so you always know where your funds are and what they are doing.
                    </p>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual">
                        <div class="card round-card gradient-glow">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small fw-semibold text-body">Why investors trust us</div>
                                    <span class="badge badge-soft-primary">Since day one</span>
                                </div>
                                <div class="my-3">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="icon-badge"><i class="fa-solid fa-shield-halved"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">Security first</div><span class="text-muted">2FA, KYC, SSL, audit logs</span></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="icon-badge"><i class="fa-solid fa-coins"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">{{ number_format((float) $ratesMin * 100, 2) }}% – {{ number_format((float) $ratesMax * 100, 2) }}% daily</div><span class="text-muted">interest credited every 24h</span></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-badge"><i class="fa-solid fa-eye"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">Full transparency</div><span class="text-muted">immutable, auditable ledger</span></div>
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
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="eyebrow mb-3"><i class="fa-solid fa-compass me-1"></i> Our mission</span>
                    <h2 class="section-title mt-2 mb-3">{{ config('app.name') }} lets you <strong>deposit, invest, and earn</strong></h2>
                    <p class="text-muted mb-3">{{ config('app.name') }} lets you deposit cryptocurrency, invest it for a
                        fixed period, and earn between {{ number_format((float) $ratesMin * 100, 2) }}% and {{ number_format((float) $ratesMax * 100, 2) }}%
                        daily interest. Interest is credited automatically to your earnings wallet every 24 hours and can be
                        withdrawn at any time, while your principal stays locked until maturity.</p>
                    <p class="text-muted mb-0">Our mission is simple: make compound, institutional-quality returns available
                        to everyone — from first-time investors to seasoned holders. Every wallet, balance, and transaction is
                        recorded on an immutable, auditable ledger.</p>

                    <div class="row g-4 mt-2">
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <div class="icon-badge"><i class="fa-solid fa-shield-halved"></i></div>
                                <div>
                                    <h6 class="fw-semibold mb-1">Security first</h6>
                                    <p class="text-muted small mb-0">2FA, KYC &amp; TLS on every action.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <div class="icon-badge"><i class="fa-solid fa-eye"></i></div>
                                <div>
                                    <h6 class="fw-semibold mb-1">Transparency</h6>
                                    <p class="text-muted small mb-0">Clear, auditable wallets.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <div class="icon-badge"><i class="fa-solid fa-headset"></i></div>
                                <div>
                                    <h6 class="fw-semibold mb-1">24/7 support</h6>
                                    <p class="text-muted small mb-0">Always here to help.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="stats-band p-4">
                        <div class="small fw-semibold text-white mb-3">Our promise at a glance</div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-top border-light small">
                            <span class="text-white-50">Daily interest</span>
                            <span class="fw-semibold text-white fs-5">{{ number_format((float) $ratesMin * 100, 2) }}% – {{ number_format((float) $ratesMax * 100, 2) }}%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-top border-light small">
                            <span class="text-white-50">Interest credit cycle</span>
                            <span class="fw-semibold text-white">Every 24h</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-top border-light small">
                            <span class="text-white-50">Principal at maturity</span>
                            <span class="fw-semibold text-white">Released</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-top border-light small">
                            <span class="text-white-50">Withdrawals</span>
                            <span class="fw-semibold text-white">Anytime</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 section-alt">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-star me-1"></i> Why us</span>
                <h2 class="section-title mb-2">Built around <strong>clear rules</strong></h2>
                <p class="section-lead mb-0">No surprises — just a simple, predictable investment model.</p>
            </div>

            <div class="row g-4">
                @php
                    $points = [
                        ['i' => 'fa-arrow-trend-up', 't' => number_format((float) $ratesMin * 100, 2).'% – '.number_format((float) $ratesMax * 100, 2).'% daily interest', 'd' => 'Your plan\'s daily rate is credited to your earnings wallet every 24 hours.'],
                        ['i' => 'fa-lock', 't' => 'Principal locked until maturity', 'd' => 'Your invested capital is safe and locked for the full plan duration.'],
                        ['i' => 'fa-sack-dollar', 't' => 'Earnings are always withdrawable', 'd' => 'Daily interest and referral income can be withdrawn any time.'],
                        ['i' => 'fa-layer-group', 't' => 'Invest in multiple plans', 'd' => 'Hold several active investments across different plans at once.'],
                        ['i' => 'fa-user-plus', 't' => 'Rewarding referrals', 'd' => 'Earn commission on qualifying investments made by those you invite.'],
                        ['i' => 'fa-file-shield', 't' => 'Immutable ledger', 'd' => 'Every transaction is permanently recorded and audit-ready.'],
                    ];
                @endphp

                @foreach ($points as $p)
                    <div class="col-md-6 col-lg-4">
                        <div class="card round-card lift h-100">
                            <div class="card-body p-4">
                                <div class="icon-badge mb-3"><i class="fa-solid {{ $p['i'] }}"></i></div>
                                <h6 class="fw-semibold">{{ $p['t'] }}</h6>
                                <p class="text-muted small mb-0">{{ $p['d'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="stats-band row g-4 p-4 p-md-5 text-center">
                <div class="col-6 col-md-3">
                    <div class="stat-value">500+</div>
                    <div class="small">Active investors</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-value">$2.4M</div>
                    <div class="small">Total invested</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-value">{{ $supportedCoins }}</div>
                    <div class="small">Supported coins</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-value">24/7</div>
                    <div class="small">Dedicated support</div>
                </div>
            </div>
        </div>
    </section>
@endsection