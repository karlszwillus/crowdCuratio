@props([
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'required' => false,
    'hint' => null,
    'error' => null,
    'id' => null,
])

@php
    $inputId = $id ?? $name;
    $hintId = $hint ? $inputId.'-hint' : null;
    $errorId = $error ? $inputId.'-error' : null;
    $describedBy = trim(($hintId ?? '').' '.($errorId ?? '')) ?: null;

    $base = 'block w-full rounded-md border border-ink-400 px-3 py-2 text-body text-ink-900 '
          . 'placeholder:text-ink-500 '
          . 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary '
          . 'disabled:bg-ink-400/10 disabled:cursor-not-allowed';

    $errorClasses = $error ? ' border-danger' : '';
@endphp

<div class="flex flex-col gap-1">
    <label for="{{ $inputId }}" class="text-caption font-medium text-ink-700">
        {{ $label }}
        @if ($required)
            <span aria-hidden="true" class="text-danger">*</span>
            <span class="sr-only">({{ __('label_mandatory') ?? 'Pflichtfeld' }})</span>
        @endif
    </label>

    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        @if ($required) required aria-required="true" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->merge(['class' => $base.$errorClasses]) }}
    />

    @if ($hint)
        <p id="{{ $hintId }}" class="text-caption text-ink-600">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $errorId }}" class="text-caption text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
