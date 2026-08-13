@extends('layouts.marketing')

@section('title', 'FAQ — '.config('app.name'))

@section('content')
    @php
        $planRates = \App\Models\InvestmentPlan::where('is_active', true)->orderBy('daily_rate')->pluck('daily_rate');
        $ratesMin = $planRates->first() ?? config('jiwa.default_daily_rate');
        $ratesMax = $planRates->last() ?? config('jiwa.default_daily_rate');
    @endphp

    <section class="hero-section py-5">
        <div class="container hero-inner">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow mb-4"><i class="fa-solid fa-circle-question me-1"></i> Help center</span>
                    <h1 class="mt-3 mb-4">Frequently asked <strong>questions</strong></h1>
                    <p class="lead mb-0">
                        Answers to the questions we hear most — from getting started to wallets, withdrawals,
                        and security.
                    </p>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual">
                        <div class="card round-card gradient-glow">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small fw-semibold text-body">Quick answers</div>
                                    <span class="badge badge-soft-success">Always on time</span>
                                </div>
                                <div class="my-3">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="icon-badge"><i class="fa-solid fa-arrow-trend-up"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">{{ number_format((float) $ratesMin * 100, 2) }}% – {{ number_format((float) $ratesMax * 100, 2) }}% daily</div><span class="text-muted">credited every 24h</span></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="icon-badge"><i class="fa-solid fa-vault"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">Principal stays locked</div><span class="text-muted">until plan maturity</span></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-badge"><i class="fa-solid fa-sack-dollar"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">Earnings anytime</div><span class="text-muted">withdrawable whenever you like</span></div>
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
            <div class="accordion" id="faqAccordion">
                @php
                    $faqs = [
                        ['q' => 'How do I start investing?', 'a' => 'Create a free account, fund your principal wallet with BTC, ETH, BNB, or USDT, then select an investment plan. Interest is credited to your account every 24 hours.'],
                        ['q' => 'What is the daily interest rate?', 'a' => 'Plans earn fixed daily rates between '.number_format((float) $ratesMin * 100, 2).'% and '.number_format((float) $ratesMax * 100, 2).'% depending on the plan you choose, credited automatically to your earnings wallet every 24 hours. Rates are admin-configurable.'],
                        ['q' => 'How are returns calculated?', 'a' => 'Interest is computed daily on the amount invested at your plan\'s rate and credited to your earnings wallet. Your principal stays locked until the plan matures, while earnings can be withdrawn anytime.'],
                        ['q' => 'What happens when my plan matures?', 'a' => 'At maturity your invested principal is released back to your principal wallet, and all earned interest remains in your earnings wallet — ready to reinvest or withdraw.'],
                        ['q' => 'What are the minimums and durations?', 'a' => 'The minimum investment is $100 on the Starter plan, rising to $500, $1,500, $5,000, and $10,000 across the Growth, Advantage, Pro, and Elite plans. Durations run 30, 90, 180, 365, and 730 days.'],
                        ['q' => 'What wallets do I get?', 'a' => 'Each account has three wallets: a Principal Wallet (locks your invested capital until maturity), an Earnings Wallet (your daily interest, withdrawable anytime), and a Referral Wallet (commission earnings, withdrawable anytime).'],
                        ['q' => 'Can I withdraw my earnings?', 'a' => 'Yes. Once you complete KYC verification and enable two-factor authentication, you can request withdrawals from your earnings and referral wallets at any time to your crypto wallet.'],
                        ['q' => 'How does the referral program work?', 'a' => 'Share your unique referral link. Once you have an active investment of '.config('jiwa.referral_qualification_minimum').' or more, your link unlocks and you earn a '.number_format(config('jiwa.referral_commission_rate') * 100, 0).'% commission on each qualifying investment made by the people you refer.'],
                        ['q' => 'Can I invest in multiple plans at once?', 'a' => 'Yes. There is no limit on the number of active investments you can hold across different plans.'],
                        ['q' => 'Is my money secure?', 'a' => 'Yes. We use hashed passwords, KYC verification, two-factor authentication, TLS/SSL, and a full audit log of every transaction. Each deposit is confirmed against the blockchain before it is credited.'],
                        ['q' => 'What cryptocurrencies can I deposit?', 'a' => 'We support Bitcoin (BTC), Ethereum (ETH), BNB (BEP-20), and USDT on TRC-20, ERC-20, and BEP-20 networks.'],
                        ['q' => 'What is the '.config('app.name').' Bot Fund?', 'a' => 'The Bot Fund is a custodial trading product: JIWA allocated by investors is pooled and traded 24/7 by an automated strategy. The bot\'s daily results are what produce your returns — profit days sweep payouts to your Earnings Wallet, while loss days reduce your position\'s value and the fund\'s net asset value. You can follow the fund\'s live candlestick chart, NAV, and your own position on the Trade hub, and release your funds back to your wallet at any time.'],
                        ['q' => 'Is the Bot Fund risky?', 'a' => 'Yes. Trading involves substantial risk of loss. Returns are variable and the bot can post losses, which reduce your position\'s value and the pooled fund\'s NAV. Past performance does not guarantee future results, and nothing we publish is financial advice — only allocate funds you can afford to lose.'],
                    ];
                @endphp

                @foreach ($faqs as $i => $faq)
                    <div class="accordion-item mb-2 border-0 rounded-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }} fw-semibold" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">
                                {{ $faq['q'] }}
                            </button>
                        </h2>
                        <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">{{ $faq['a'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection