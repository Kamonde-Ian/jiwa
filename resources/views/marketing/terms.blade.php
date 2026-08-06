@extends('layouts.marketing')

@section('title', 'Terms of Service — '.config('app.name'))

@section('content')
    <section class="hero-section py-5">
        <div class="container hero-inner">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow mb-4"><i class="fa-solid fa-file-contract me-1"></i> Legal</span>
                    <h1 class="mt-3 mb-4">Terms of <strong>Service</strong></h1>
                    <p class="lead mb-0">
                        Please read these terms carefully before using the platform — they govern access,
                        investments, and your responsibilities as a {{ config('app.name') }} user.
                    </p>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual">
                        <div class="card round-card gradient-glow">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="small fw-semibold text-body">Key points</div>
                                    <span class="badge badge-soft-primary">Legal</span>
                                </div>
                                <div class="small">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="text-muted">Clear, transparent terms</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="text-muted">Investments carry risk</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="text-muted">18+ eligibility required</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="text-muted">No hidden fees</span>
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
        <div class="container text-muted" style="max-width:44rem">
            <h5 class="text-body mb-2">1. Acceptance of terms</h5>
            <p>By accessing or using {{ config('app.name') }}, you agree to be bound by these Terms of Service and all applicable laws. If you do not agree, please do not use the platform.</p>

            <h5 class="text-body mb-2 mt-4">2. Eligibility</h5>
            <p>You must be at least 18 years old and capable of entering into a binding contract to create an account. You are responsible for the accuracy of the information you provide.</p>

            <h5 class="text-body mb-2 mt-4">3. Investments &amp; risk</h5>
            <p>All investments carry risk, and past performance does not guarantee future results. Returns are displayed for informational purposes. You should only invest funds you are prepared to risk.</p>

            <h5 class="text-body mb-2 mt-4">4. Acceptable use</h5>
            <p>You agree not to use the platform for any unlawful purpose, to misrepresent your identity, or to attempt to disrupt or gain unauthorized access to the platform.</p>

            <h5 class="text-body mb-2 mt-4">5. Limitation of liability</h5>
            <p>To the maximum extent permitted by law, {{ config('app.name') }} shall not be liable for any indirect, incidental, or consequential damages arising from your use of the platform.</p>

            <p class="mt-4">Questions about these terms? <a href="{{ route('public.contact') }}" wire:navigate>Contact us</a>.</p>
        </div>
    </section>
@endsection