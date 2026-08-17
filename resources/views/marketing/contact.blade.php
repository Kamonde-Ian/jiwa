@extends('layouts.marketing')

@php $support = config('jiwa.support_email'); @endphp

@section('title', 'Contact — '.config('app.name'))

@section('content')
    <section class="hero-section py-5">
        <div class="container hero-inner">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow mb-4"><i class="fa-solid fa-envelope-open-text me-1"></i> Get in touch</span>
                    <h1 class="mt-3 mb-4">Contact <strong>us</strong></h1>
<p class="lead mb-0">
                        Have a question about investing, your account, or a withdrawal? Send us a message and we'll
                        get back to you quickly.
                    </p>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual">
                        <div class="card round-card gradient-glow">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small fw-semibold text-body">We reply fast</div>
                                    <span class="badge badge-soft-success">24/7</span>
                                </div>
                                <div class="my-3">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="icon-badge"><i class="fa-solid fa-envelope"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">Email support</div><span class="text-muted">{{ $support }}</span></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="icon-badge"><i class="fa-solid fa-comments"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">Live chat</div><span class="text-muted">around the clock</span></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-badge"><i class="fa-solid fa-shield-halved"></i></div>
                                        <div class="small"><div class="fw-semibold text-body">Security</div><span class="text-muted">confidential handling</span></div>
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
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4 text-center">
                            <div class="icon-badge mx-auto mb-3"><i class="fa-solid fa-envelope"></i></div>
                            <h6 class="fw-semibold">Email support</h6>
                            <p class="text-muted small mb-0">{{ $support }}<br>for account &amp; withdrawal help</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4 text-center">
                            <div class="icon-badge mx-auto mb-3"><i class="fa-solid fa-comments"></i></div>
                            <h6 class="fw-semibold">Live chat</h6>
                            <p class="text-muted small mb-0">24/7 live assistance<br>from our support team</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card round-card lift h-100">
                        <div class="card-body p-4 text-center">
                            <div class="icon-badge mx-auto mb-3"><i class="fa-solid fa-shield-halved"></i></div>
                            <h6 class="fw-semibold">Security issues</h6>
                            <p class="text-muted small mb-0">Report account anomalies<br>for confidential handling</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 section-alt">
        <div class="container" style="max-width:44rem">
            <div class="section-head mb-4">
                <span class="eyebrow mb-3"><i class="fa-solid fa-paper-plane me-1"></i> Send a message</span>
                <h2 class="section-title mb-2">We usually reply <strong>within hours</strong></h2>
            </div>

            @if (session('status'))
                <div class="alert alert-success rounded-3">
                    <i class="fa-solid fa-circle-check me-2"></i>{{ session('status') }}
                </div>
            @endif

            <div class="card round-card">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="{{ route('public.contact.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Your name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                    class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email address</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" value="{{ old('subject') }}"
                                    class="form-control @error('subject') is-invalid @enderror" required>
                                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="message">Message</label>
                                <textarea id="message" name="message" rows="5"
                                    class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 text-center">
        <div class="container" style="max-width:40rem">
            <div class="icon-badge mx-auto mb-3"><i class="fa-solid fa-circle-check"></i></div>
            <h4 class="fw-bold text-body mb-2">Prefer to skip the form?</h4>
            <p class="text-muted mb-3">For urgent issues, email us directly at
                <a href="mailto:{{ $support }}" class="text-decoration-none fw-semibold">{{ $support }}</a>.
            </p>
            <a href="{{ route('register') }}" class="btn btn-primary px-4">Create an account</a>
        </div>
    </section>
@endsection