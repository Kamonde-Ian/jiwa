@extends('layouts.marketing')

@section('title', 'Privacy Policy — '.config('app.name'))

@section('content')
    <section class="hero-section py-5">
        <div class="container hero-inner">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow mb-4"><i class="fa-solid fa-lock me-1"></i> Your data</span>
                    <h1 class="mt-3 mb-4">Privacy <strong>Policy</strong></h1>
                    <p class="lead mb-0">
                        How we collect, use, and protect your information — and the controls you have over your
                        personal data.
                    </p>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual">
                        <div class="card round-card gradient-glow">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="small fw-semibold text-body">Your data, your control</div>
                                    <span class="badge badge-soft-success">Protected</span>
                                </div>
                                <div class="small">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="text-muted">Encrypted at rest &amp; in transit</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="text-muted">We never sell your data</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="text-muted">Access, correct, or delete anytime</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="text-muted">2FA secrets stored encrypted</span>
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
            <h5 class="text-body mb-2">1. Information we collect</h5>
            <p>We collect the information you provide during registration (name, email, phone, country) and identity verification for KYC, along with your transaction and ledger records.</p>

            <h5 class="text-body mb-2 mt-4">2. How we use information</h5>
            <p>Your information is used to operate your account, process investments and withdrawals, prevent fraud, comply with legal obligations, and improve our services.</p>

            <h5 class="text-body mb-2 mt-4">3. Data security</h5>
            <p>We implement encryption, access controls, and regular audits to protect your data. Sensitive fields such as two-factor secrets are stored encrypted and never exposed.</p>

            <h5 class="text-body mb-2 mt-4">4. Sharing</h5>
            <p>We do not sell your personal data. We may share information with service providers or authorities where required by law.</p>

            <h5 class="text-body mb-2 mt-4">5. Your rights</h5>
            <p>Depending on your jurisdiction, you may have the right to access, correct, or delete your personal data. Contact us to exercise these rights.</p>

            <p class="mt-4">Questions about this policy? <a href="{{ route('public.contact') }}" wire:navigate>Contact us</a>.</p>
        </div>
    </section>
@endsection