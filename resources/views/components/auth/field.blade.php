@props([
    'for' => null,
    'label' => null,
    'type' => 'text',
    'model' => null,
    'placeholder' => null,
    'autocomplete' => 'off',
    'required' => false,
    'autofocus' => false,
    'inputmode' => null,
    'maxlength' => null,
    'centered' => false,
    'uppercase' => false,
    'spaced' => false,
    'class' => '',
])

<div class="auth-field">
    @if ($label)
        <label for="{{ $for }}" class="form-label small fw-semibold">{{ $label }}</label>
    @endif

    <div class="auth-field-control" data-auth-input>
        <input
            id="{{ $for }}"
            type="{{ $type }}"
            name="{{ $for }}"
            wire:model="{{ $model }}"
            @if ($required) required @endif
            @if ($autofocus) autofocus @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
            @if ($maxlength) maxlength="{{ $maxlength }}" @endif
            autocomplete="{{ $autocomplete }}"
            placeholder="{{ $placeholder }}"
            @class([
                'form-control auth-input',
                'is-invalid' => $errors->has($model),
                'text-center' => $centered,
                'text-uppercase' => $uppercase,
                'letter-spacing-lg' => $spaced,
                $class,
            ])
        >
        <span class="auth-field-glow auth-field-glow-top" data-glow-top></span>
        <span class="auth-field-glow auth-field-glow-bottom" data-glow-bottom></span>
    </div>

    @error($model)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
