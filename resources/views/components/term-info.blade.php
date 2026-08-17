@props([
    'term' => null,
    'tip' => null,
    'placement' => 'top',
    'label' => null,
])

@php
    if ($term) {
        $key = \Illuminate\Support\Str::slug($term, '_');
        $tooltip = $tip ?? config('glossary.' . $key, '');
        $cacheKey = $key;
    } else {
        $cacheKey = '';
        $tooltip = $tip ?? '';
    }
    $id = 'term-info-' . $cacheKey . '-' . \Illuminate\Support\Str::random(5);
@endphp

<span
    id="{{ $id }}"
    class="term-info"
    data-bs-toggle="tooltip"
    data-bs-placement="{{ $placement }}"
    data-bs-title="{{ $tooltip }}"
    tabindex="0"
    role="button"
    aria-label="{{ $term }} — what is this?"
>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @elseif ($label)
        {{ $label }}
    @else
        <i class="fa-solid fa-circle-question"></i>
    @endif
</span>