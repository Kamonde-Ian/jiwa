@extends('layouts.marketing')

@section('title', config('app.name').' — Secure Crypto Investment Platform')

@section('content')
    <section class="hero-section">
        <div class="container hero-inner">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow mb-4"><i class="fa-solid fa-shield-halved me-1"></i> Welcome to {{ config('app.name') }}</span>
                    <h1 class="mt-3 mb-4">Grow your wealth with daily-return crypto investments</h1>
                    <p class="lead mb-4">
                        Invest in curated digital-asset portfolios, earn predictable daily returns, and withdraw anytime.
                        Start from as little as ${{ config('jiwa.min_investment') }}.
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">Start Investing <i class="fa-solid fa-arrow-right ms-2"></i></a>
                        <a href="{{ route('public.plans') }}" class="btn btn-outline-primary btn-lg px-4" wire:navigate>Know More</a>
                    </div>
                </div>

                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual">
                        <div class="card round-card gradient-glow">
                            <div class="card-body p-4">
                                <div id="hero-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="stats-band row g-4 p-4 p-md-5 text-center">
                <div class="col-6 col-md-3 col-xl-3">
                    <div class="stat-value">500+</div>
                    <div class="small">Active investors</div>
                </div>
                <div class="col-6 col-md-3 col-xl-3">
                    <div class="stat-value">$2.4M</div>
                    <div class="small">Total invested</div>
                </div>
<div class="col-6 col-md-3 col-xl-3">
                    <div class="stat-value">{{ count(config('jiwa.countries')) }}+</div>
                    <div class="small">Countries served</div>
                </div>
                <div class="col-6 col-md-3 col-xl-3">
                    <div class="stat-value">24/7</div>
                    <div class="small">Dedicated support</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-gem me-1"></i> What we offer</span>
                <h2 class="section-title mb-3">Everything you need to <strong>invest with confidence</strong></h2>
                <p class="section-lead mb-0">A complete toolkit for growing your digital wealth — automated, transparent, and secure.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card round-card feature-card lift h-100">
                        <div class="icon-badge mb-3"><i class="fa-solid fa-chart-line"></i></div>
                        <h6 class="feature-title">Daily Returns</h6>
                        <p class="feature-text mb-3">Earnings accrue to your account every single day, automatically and on time.</p>
                        <a href="{{ route('public.plans') }}" class="link-more mt-auto" wire:navigate>Explore plans <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card round-card feature-card lift h-100">
                        <div class="icon-badge mb-3"><i class="fa-solid fa-user-plus"></i></div>
                        <h6 class="feature-title">Referral Rewards</h6>
                        <p class="feature-text mb-3">Earn commissions when friends join and make qualifying investments.</p>
                        <a href="{{ route('public.about') }}" class="link-more mt-auto" wire:navigate>Learn more <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card round-card feature-card lift h-100">
                        <div class="icon-badge mb-3"><i class="fa-solid fa-shield-halved"></i></div>
                        <h6 class="feature-title">Secure &amp; Audited</h6>
                        <p class="feature-text mb-3">2FA, KYC, and encrypted ledgers keep every wallet safe.</p>
                        <a href="{{ route('public.faq') }}" class="link-more mt-auto" wire:navigate>Read FAQ <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card round-card feature-card lift h-100">
                        <div class="icon-badge mb-3"><i class="fa-solid fa-bolt"></i></div>
                        <h6 class="feature-title">Fast Withdrawals</h6>
                        <p class="feature-text mb-3">Request payouts anytime with transparent, admin-free processing.</p>
                        <a href="{{ route('public.contact') }}" class="link-more mt-auto" wire:navigate>Contact us <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 section-alt">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-coins me-1"></i> Investment plans</span>
                <h2 class="section-title mb-2">Choose your <strong>investment plan</strong></h2>
                <p class="section-lead mb-0">Simple plans with fixed durations and clear daily returns.</p>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach (\App\Models\InvestmentPlan::where('is_active', true)->orderBy('min_amount')->get() as $plan)
                    @php
                        $isPopular = $plan->description === 'Most Popular';
                        $emoji = match ($plan->name) { 'Growth' => '⭐', 'Elite' => '👑', default => '' };
                        $rangeLabel = $plan->max_amount === null
                            ? '$' . number_format((float) $plan->min_amount, 0) . '+'
                            : '$' . number_format((float) $plan->min_amount, 0) . '–' . number_format((float) $plan->max_amount, 0);
                        $maxLabel = $plan->max_amount === null ? 'Unlimited' : '$' . number_format((float) $plan->max_amount, 0);
                    @endphp
                    <div class="col-sm-6 col-md-4 col-xl">
                        <div class="card round-card lift h-100 {{ $isPopular ? 'plan-featured' : '' }}">
                            <div class="card-body p-4 text-center">
                                <div class="badge badge-soft-primary mb-1 text-uppercase fs-6">{{ $plan->name }} {{ $emoji }}</div>
                                <div class="text-muted small mb-3 text-uppercase" style="letter-spacing:.5px">{{ $plan->description }}</div>
                                <div class="fs-3 fw-bold text-body mb-1">{{ number_format($plan->daily_rate * 100, 2) }}%<span class="fs-6 text-muted fw-normal"> /day</span></div>
                                <div class="text-muted small mb-3">{{ $plan->duration_days }} days · {{ $rangeLabel }}</div>
                                <div class="d-flex justify-content-between border-top py-2 small">
                                    <span class="text-muted">Minimum</span>
                                    <span class="fw-semibold">${{ number_format($plan->min_amount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-2 small">
                                    <span class="text-muted">Maximum</span>
                                    <span class="fw-semibold">{{ $maxLabel }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-2 small">
                                    <span class="text-muted">Est. total return (min)</span>
                                    <span class="fw-semibold text-success">${{ number_format($plan->min_amount + $plan->min_amount * $plan->daily_rate * $plan->duration_days, 2) }}</span>
                                </div>
                                <a href="{{ route('register') }}" class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} w-100 mt-3">Invest Now</a>
                            </div>
                        </div>
                    </div>
@endforeach
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('public.plans') }}" class="link-more" wire:navigate>View all plans &amp; compare <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-robot me-1"></i> Custodial trading</span>
                <h2 class="section-title mb-2">Meet the <strong>{{ config('app.name') }} Bot Fund</strong></h2>
                <p class="section-lead mb-0">A pooled JIWA fund traded around the clock by our automated strategy — its results are what produce your daily returns.</p>
            </div>

            <div class="marketing-trade-card">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <h4 class="fw-bold mb-3">Your capital compounds from real trading, not a fixed rate</h4>
                        <ul class="trade-feature-list mb-4">
                            <li><i class="fa-solid fa-circle-check"></i>Allocate JIWA from your principal wallet into the pooled Bot Fund.</li>
                            <li><i class="fa-solid fa-circle-check"></i>The bot trades the pooled deposits 24/7; daily results are credited to your Earnings Wallet.</li>
                            <li><i class="fa-solid fa-circle-check"></i>Track the fund's candlestick chart, NAV and your position live on the Trade hub.</li>
                            <li><i class="fa-solid fa-circle-check"></i>Release funds back to your wallet anytime — fully transparent ledger on every payout.</li>
                        </ul>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">Start Trading <i class="fa-solid fa-arrow-right ms-2"></i></a>
                            <a href="{{ route('public.faq') }}" class="btn btn-outline-primary btn-lg px-4" wire:navigate>See FAQ</a>
                        </div>
                        <div class="risk-disclaimer">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div>
                                <strong>Risk disclaimer.</strong>&nbsp;Trading involves substantial risk of loss. Returns are variable — the bot can post losses, which reduce both your position's value and the NAV of the pooled fund. Past performance does not guarantee future results. Nothing on this page is financial advice; only trade with funds you can afford to lose.
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 d-none d-lg-block">
                        <div class="trade-visual">
                            <div class="icon-badge icon-badge-lg mb-3"><i class="fa-solid fa-robot"></i></div>
                            <div class="fs-5 fw-bold mb-1">JIWA Bot Fund</div>
                            <div class="text-muted small mb-4">Deterministic strategy · transparent ledger · daily settlement · custodied on-platform</div>
                            <div class="fake-ticker">
                                <span>Strategy <b>GridScalper</b></span>
                                <span>Settlement <b>Every 24h</b></span>
                                <span>Access <b>Live chart + NAV</b></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-shoe-prints me-1"></i> How it works</span>
                <h2 class="section-title mb-2">Three steps to <strong>start earning</strong></h2>
            </div>

            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="icon-badge icon-badge-lg mb-3">1</div>
                    <h6 class="fw-semibold">Create your account</h6>
                    <p class="text-muted small">Sign up in under a minute and secure it with two-factor authentication.</p>
                </div>
                <div class="col-md-4">
                    <div class="icon-badge icon-badge-lg mb-3">2</div>
                    <h6 class="fw-semibold">Fund your wallet</h6>
                    <p class="text-muted small">Deposit BTC, ETH, BNB, or USDT into your principal wallet.</p>
                </div>
                <div class="col-md-4">
                    <div class="icon-badge icon-badge-lg mb-3">3</div>
                    <h6 class="fw-semibold">Invest &amp; earn</h6>
                    <p class="text-muted small">Pick a plan and watch your daily returns accumulate.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 section-alt">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-shield-halved me-1"></i> Why us</span>
                <h2 class="section-title mb-2">Why investors choose <strong>{{ config('app.name') }}</strong></h2>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="icon-badge mb-3"><i class="fa-solid fa-lock"></i></div>
                    <h6 class="fw-semibold">Bank-grade security</h6>
                    <p class="text-muted small mb-0">Two-factor authentication, KYC verification, and encrypted storage protect every account and transaction.</p>
                </div>
                <div class="col-md-4">
                    <div class="icon-badge mb-3"><i class="fa-solid fa-receipt"></i></div>
                    <h6 class="fw-semibold">Full transparency</h6>
                    <p class="text-muted small mb-0">A complete, auditable ledger shows every deposit, interest credit, and withdrawal — no surprises.</p>
                </div>
                <div class="col-md-4">
                    <div class="icon-badge mb-3"><i class="fa-solid fa-mobile-screen-button"></i></div>
                    <h6 class="fw-semibold">Invest on the go</h6>
                    <p class="text-muted small mb-0">Manage your portfolio from any device, with a fast, responsive experience built for everyone.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-coins me-1"></i> Assets</span>
                <h2 class="section-title mb-2">Assets <strong>we support</strong></h2>
                <p class="section-lead mb-0">Fund your investments using major cryptocurrencies.</p>
            </div>

            <div class="row g-4 justify-content-center text-center">
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#f7931a;background:rgba(247,147,26,.12)"><i class="fa-brands fa-bitcoin"></i></div>
                            <div class="fw-semibold">Bitcoin</div>
                            <div class="text-muted small">BTC</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#627eea;background:rgba(98,126,234,.12)"><i class="fa-brands fa-ethereum"></i></div>
                            <div class="fw-semibold">Ethereum</div>
                            <div class="text-muted small">ETH</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#26a17b;background:rgba(38,161,123,.12)"><i class="fa-solid fa-coins"></i></div>
                            <div class="fw-semibold">USDT</div>
                            <div class="text-muted small">TRC-20</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#26a17b;background:rgba(38,161,123,.12)"><i class="fa-solid fa-coins"></i></div>
                            <div class="fw-semibold">USDT</div>
                            <div class="text-muted small">ERC-20</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#26a17b;background:rgba(38,161,123,.12)"><i class="fa-solid fa-coins"></i></div>
                            <div class="fw-semibold">USDT</div>
                            <div class="text-muted small">BEP-20</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#f0b90b;background:rgba(240,185,11,.12)"><i class="fa-solid fa-coins"></i></div>
                            <div class="fw-semibold">BNB</div>
                            <div class="text-muted small">BEP-20</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 section-alt">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-comments me-1"></i> Testimonials</span>
                <h2 class="section-title mb-2">What our <strong>investors say</strong></h2>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4">
                            <div class="text-warning mb-2"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                            <p class="small mb-3">"The dashboard is so clear — I can see my daily interest accumulating in real time. Withdrawals were surprisingly fast."</p>
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-semibold" style="width:2.5rem;height:2.5rem;background:var(--sneat-primary)">A</div>
                                <div>
                                    <div class="fw-semibold small">Amelia R.</div>
                                    <div class="text-muted small">Starter plan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4">
                            <div class="text-warning mb-2"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                            <p class="small mb-3">"Referrals actually pay out. I refer friends and the commission lands in my earnings wallet every time."</p>
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-semibold" style="width:2.5rem;height:2.5rem;background:var(--sneat-primary-dark)">D</div>
                                <div>
                                    <div class="fw-semibold small">David M.</div>
                                    <div class="text-muted small">Growth plan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4">
                            <div class="text-warning mb-2"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                            <p class="small mb-3">"I started with a small deposit to test, and the support team answered every question quickly. Highly recommend."</p>
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-semibold" style="width:2.5rem;height:2.5rem;background:var(--sneat-primary-dark)">S</div>
                                <div>
                                    <div class="fw-semibold small">Sofia L.</div>
                                    <div class="text-muted small">Advantage plan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 text-center text-white" style="background:linear-gradient(135deg,#E6B947,#C8942A 55%,#986817)">
        <div class="container py-3">
            <h2 class="text-white mb-3">Ready to start earning?</h2>
            <p class="mb-4 text-white-50">Join hundreds of investors building long-term wealth with {{ config('app.name') }}.</p>
            <a href="{{ route('register') }}" class="btn btn-light btn-lg px-4">Get Started Free</a>
        </div>
    </section>
@endsection

@section('scripts')
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('hero-chart');
        if (el && window.ApexCharts) {
            const AXIS = '#8a94a6';
            const GOLD = '#C8942A';
            const BLUE = '#2962ff';
            const SELL = '#f23645';
            const UP = '#16a34a';
            const DOWN = '#dc2626';

            const N = 30; // candles on screen
            const STEP_MS = 1400; // new candle every 1.4s
            let startTs = Date.now() - N * 1400;
            let t = 0;
            let state = 100;
            let data = []; // {x, y:[open, high, low, close]}
            let drawn = {}; // annotation ids currently on the chart
            let markerSeq = 0;

            // Realistic market model: gentle upward bias, multi-scale waves,
            // mean reversion to recent closes and bounded noise.
            const nextClose = () => {
                const drift = 0.09;
                const wave =
                    1.8 * Math.sin(t / 9) +
                    1.1 * Math.sin(t / 4.3) +
                    0.45 * Math.sin(t / 1.9);
                const closes = data.slice(-8).map((d) => d.y[3]);
                const sma = closes.length ? closes.reduce((a, b) => a + b, 0) / closes.length : state;
                const reversion = (sma - state) * 0.10;
                const noise = (Math.random() - 0.5) * 0.9;
                return Math.max(40, state + drift + wave + reversion + noise);
            };

            const pushCandle = () => {
                const open = state;
                const close = nextClose();
                const high = Math.max(open, close) + Math.random() * 1.1;
                const low = Math.min(open, close) - Math.random() * 1.1;
                data.push({
                    x: new Date(startTs + t * STEP_MS),
                    y: [open, high, low, close],
                });
                state = close;
            };

            const buildData = () => {
                data = [];
                state = 100;
                startTs = Date.now() - N * STEP_MS;
                t = 0;
                for (let i = 0; i < N; i++) {
                    t++;
                    pushCandle();
                }
            };

            // The line curve: each point is the midpoint of the candle's
            // high and low, so it moves with the candle ranges.
            const marketData = () => data.map((d) => ({
                x: d.x,
                y: (d.y[1] + d.y[2]) / 2,
            }));

            buildData();

            const chart = new ApexCharts(el, {
                chart: {
                    type: 'candlestick',
                    height: 230,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: true, easing: 'easeinout', speed: 500, dynamicAnimation: { enabled: true, speed: 300 } },
                },
                series: [
                    { name: 'Price', type: 'candlestick', data },
                    { name: 'Market', type: 'line', data: marketData() },
                ],
                plotOptions: {
                    candlestick: {
                        colors: { upward: UP, downward: DOWN },
                        wick: { useFillColor: true },
                    },
                },
                stroke: { curve: 'straight', width: [2, 1.5], colors: [GOLD, GOLD] },
                colors: [GOLD, GOLD],
                legend: { show: false },
                xaxis: {
                    type: 'datetime',
                    labels: { show: true, datetimeUTC: false, style: { colors: AXIS, fontSize: '11px' }, formatter: (val) => new Date(val).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    opposite: true,
                    labels: { show: true, style: { colors: AXIS, fontSize: '11px' }, formatter: (v) => v.toFixed(2) },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                grid: {
                    show: true,
                    borderColor: '#e6e8ec',
                    strokeDashArray: 0,
                    position: 'back',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: true } },
                },
                dataLabels: { enabled: false },
                tooltip: { enabled: false },
            });
            chart.render();

            const refreshChartTheme = () => {
                const dark = document.documentElement.getAttribute('data-theme') === 'dark';
                const fg = dark ? '#C9BFA3' : AXIS;
                chart.updateOptions({
                    chart: { foreColor: fg },
                    grid: { borderColor: dark ? 'rgba(216, 168, 57, 0.2)' : '#e6e8ec' },
                    xaxis: { labels: { style: { colors: fg } } },
                    yaxis: { labels: { style: { colors: fg } } },
                }, false);
            };
            window.addEventListener('theme-changed', refreshChartTheme);

            // ---- MT5-style position simulation ----
            // Walk the candles and simulate trades. There is always an open
            // position: a long when price is above the short EMA (blue line),
            // a short when below it (red line). When take-profit or stop-loss
            // is hit the position closes and a new one opens on the same
            // candle, so the dashed line is always present.
            const simulateTrades = () => {
                let pos = null;
                let ema = null;
                const items = [];

                const lineColor = (dir) => (dir === 'buy' ? BLUE : SELL);

                const openPos = (i, d, dir) => {
                    pos = { dir, entry: d.y[3], openX: d.x, lineId: 'pos-line-' + (++markerSeq) };
                    const id = 'entry-' + (++markerSeq);
                    items.push({
                        id,
                        kind: 'point',
                        opts: {
                            id,
                            x: d.x,
                            y: d.y[3],
                            marker: { size: 6, fillColor: lineColor(dir), strokeColor: '#ffffff', strokeWidth: 1.5 },
                            label: {
                                text: (dir === 'buy' ? 'BUY ▲ ' : 'SELL ▼ ') + d.y[3].toFixed(2),
                                borderColor: lineColor(dir),
                                offsetY: -12,
                                style: { background: lineColor(dir), color: '#fff', fontSize: '10px', fontWeight: 700, padding: { left: 4, right: 4, top: 2, bottom: 2 } },
                            },
                        },
                    });
                };

                for (let i = 0; i < data.length; i++) {
                    const d = data[i];
                    const [o, hi, lo, c] = d.y;
                    ema = ema === null ? c : c * 0.4 + ema * 0.6;

                    if (!pos) {
                        openPos(i, d, c >= ema ? 'buy' : 'sell');
                        continue;
                    }

                    const isBuy = pos.dir === 'buy';
                    const tp = isBuy ? pos.entry * 1.012 : pos.entry * 0.988;
                    const sl = isBuy ? pos.entry * 0.988 : pos.entry * 1.012;
                    const hitTp = isBuy ? hi >= tp : lo <= tp;
                    const hitSl = isBuy ? lo <= sl : hi >= sl;

                    if (hitTp || hitSl) {
                        const closedDir = pos.dir;
                        const exitY = hitTp ? tp : sl;
                        const id = 'exit-' + (++markerSeq);
                        items.push({
                            id,
                            kind: 'point',
                            opts: {
                                id,
                                x: d.x,
                                y: exitY,
                                marker: { size: 5, fillColor: DOWN, strokeColor: '#ffffff', strokeWidth: 1.5 },
                            },
                        });
                        pos = null;
                        // Immediately open the next trade in the opposite
                        // direction, so the blue (buy) and red (sell) lines
                        // both appear and a line is always present.
                        openPos(i, d, closedDir === 'buy' ? 'sell' : 'buy');
                    }
                }

                if (pos) {
                    const color = lineColor(pos.dir);
                    items.push({
                        id: pos.lineId,
                        kind: 'y',
                        opts: {
                            id: pos.lineId,
                            y: pos.entry,
                            borderColor: color,
                            strokeDashArray: 4,
                            label: {
                                text: (pos.dir === 'buy' ? 'BUY ▲ ' : 'SELL ▼ ') + pos.entry.toFixed(2),
                                position: 'left',
                                offsetX: 10,
                                style: { background: color, color: '#fff', fontSize: '11px', fontWeight: 700, padding: { left: 6, right: 6, top: 3, bottom: 3 } },
                            },
                        },
                    });
                }

                // Drop markers that have scrolled off the visible window.
                const minX = data.length ? data[0].x : 0;
                return items.filter((a) => a.kind === 'y' || a.opts.x >= minX);
            };

            const syncAnnotations = (items) => {
                const ids = new Set(items.map((a) => a.id));
                Object.keys(drawn).forEach((id) => {
                    if (!ids.has(id)) {
                        try { chart.removeAnnotation(id); } catch (e) {}
                        delete drawn[id];
                    }
                });
                items.forEach((a) => {
                    if (!drawn[a.id]) {
                        if (a.kind === 'y') chart.addYaxisAnnotation(a.opts);
                        else chart.addPointAnnotation(a.opts);
                        drawn[a.id] = true;
                    }
                });
            };

            const update = () => {
                chart.updateSeries([
                    { name: 'Price', type: 'candlestick', data },
                    { name: 'Market', type: 'line', data: marketData() },
                ]);
                syncAnnotations(simulateTrades());
            };

            syncAnnotations(simulateTrades());

            setInterval(() => {
                t++;
                pushCandle();
                if (data.length > N) data = data.slice(-N);
                update();
            }, STEP_MS);
        }
    });
</script>
@endsection