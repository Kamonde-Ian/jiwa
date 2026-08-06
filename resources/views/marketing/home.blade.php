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
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="small fw-semibold text-body">Portfolio Balance</div>
                                    <span class="badge badge-soft-success">+2.4% today</span>
                                </div>
                                <div class="fs-3 fw-bold text-body">$ {{ number_format(12_480.50, 2) }}</div>

                                <div class="my-4" id="hero-chart"></div>

                                <div class="d-flex justify-content-between border-top pt-3 mt-2 small">
                                    <span class="text-muted">Daily return</span>
                                    <span class="fw-semibold text-body">+$54.20</span>
                                </div>
                                <div class="d-flex justify-content-between border-top py-2 small">
                                    <span class="text-muted">Total earned</span>
                                    <span class="fw-semibold text-body">$2,194.10</span>
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
                    <div class="stat-value">40+</div>
                    <div class="small">Countries served</div>
                </div>
                <div class="col-6 col-md-3">
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

    <section class="py-5" style="background:#f7f7fa">
        <div class="container">
            <div class="section-head mb-5">
                <span class="eyebrow mb-3"><i class="fa-solid fa-coins me-1"></i> Investment plans</span>
                <h2 class="section-title mb-2">Choose your <strong>investment plan</strong></h2>
                <p class="section-lead mb-0">Simple plans with fixed durations and clear daily returns.</p>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach (\App\Models\InvestmentPlan::where('is_active', true)->orderBy('min_amount')->get() as $plan)
                    <div class="col-md-6 col-lg-4">
                        <div class="card round-card lift h-100">
                            <div class="card-body p-4 text-center">
                                <div class="badge badge-soft-primary mb-3 text-uppercase fs-6">{{ $plan->name }}</div>
                                <div class="fs-3 fw-bold text-body mb-1">{{ number_format($plan->daily_rate * 100, 2) }}%<span class="fs-6 text-muted fw-normal"> /day</span></div>
                                <div class="text-muted small mb-3">{{ $plan->duration_days }} days · min ${{ number_format($plan->min_amount, 2) }}</div>
                                <div class="d-flex justify-content-between border-top py-2 small">
                                    <span class="text-muted">Minimum</span>
                                    <span class="fw-semibold">${{ number_format($plan->min_amount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-2 small">
                                    <span class="text-muted">Est. total return</span>
                                    <span class="fw-semibold text-success">${{ number_format($plan->min_amount + $plan->min_amount * $plan->daily_rate * $plan->duration_days, 2) }}</span>
                                </div>
                                <a href="{{ route('register') }}" class="btn btn-primary w-100 mt-3">Invest Now</a>
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
                    <p class="text-muted small">Deposit BTC, ETH, or USDT into your principal wallet.</p>
                </div>
                <div class="col-md-4">
                    <div class="icon-badge icon-badge-lg mb-3">3</div>
                    <h6 class="fw-semibold">Invest &amp; earn</h6>
                    <p class="text-muted small">Pick a plan and watch your daily returns accumulate.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" style="background:#f7f7fa">
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
                <div class="col-6 col-md-3">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#f7931a;background:rgba(247,147,26,.12)"><i class="fa-brands fa-bitcoin"></i></div>
                            <div class="fw-semibold">Bitcoin</div>
                            <div class="text-muted small">BTC</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#627eea;background:rgba(98,126,234,.12)"><i class="fa-brands fa-ethereum"></i></div>
                            <div class="fw-semibold">Ethereum</div>
                            <div class="text-muted small">ETH</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#26a17b;background:rgba(38,161,123,.12)"><i class="fa-solid fa-coins"></i></div>
                            <div class="fw-semibold">USDT</div>
                            <div class="text-muted small">TRC-20</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#26a17b;background:rgba(38,161,123,.12)"><i class="fa-solid fa-coins"></i></div>
                            <div class="fw-semibold">USDT</div>
                            <div class="text-muted small">ERC-20</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card round-card lift h-100">
                        <div class="card-body py-4">
                            <div class="icon-badge icon-badge-lg mx-auto mb-3" style="color:#26a17b;background:rgba(38,161,123,.12)"><i class="fa-solid fa-coins"></i></div>
                            <div class="fw-semibold">USDT</div>
                            <div class="text-muted small">BEP-20</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
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

    <section class="py-5" style="background:#f7f7fa">
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

    <section class="py-5 text-center text-white" style="background:linear-gradient(135deg,#696cff,#5f61e6)">
        <div class="container py-3">
            <h2 class="text-white mb-3">Ready to start earning?</h2>
            <p class="mb-4 text-white-50">Join hundreds of investors building long-term wealth with JIWA.</p>
            <a href="{{ route('register') }}" class="btn btn-light btn-lg px-4">Get Started Free</a>
        </div>
    </section>
@endsection

@section('scripts')
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('hero-chart');
        if (el && window.ApexCharts) {
            new ApexCharts(el, {
                chart: { type: 'area', height: 180, toolbar: { show: false }, sparkline: { enabled: true } },
                series: [{ name: 'Balance', data: [100, 120, 115, 140, 138, 165, 158, 190, 210, 240, 235, 268] }],
                stroke: { curve: 'smooth', width: 2.5, colors: ['#696cff'] },
                fill: { gradient: { opacityFrom: 0.25, opacityTo: 0 } },
                colors: ['#696cff'],
            }).render();
        }
    });
</script>
@endsection