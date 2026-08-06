@php
    $stats = $stats ?? [];
    $subtitle = $subtitle ?? '';
@endphp

<div class="page-hero mb-4">
    <div class="row align-items-center g-3">
        <div class="col-12 col-lg-6">
            <h4 class="mb-1">{{ $title }}</h4>
            @if ($subtitle)
                <p class="mb-0 opacity-75">{{ $subtitle }}</p>
            @endif
        </div>
        @if (count($stats))
            <div class="col-12 col-lg-6">
                <div class="row g-2">
                    @foreach ($stats as $stat)
                        <div class="col-6 col-md">
                            <div class="page-hero-stat">
                                <small>{{ $stat['label'] }}</small>
                                <b>{{ $stat['value'] }}</b>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
