{{-- 5aa.3 Field-Partial: eine Zeile in der Übersetzen-Tabelle mit Original
     links und Übersetzungs-Input rechts. `data-translated` steuert den
     Filter „Nur unübersetzte Felder". --}}
@php
    $isTranslated = ! empty(trim(strip_tags((string) ($translationHtml ?? ''))));
    $multiline = $multiline ?? false;
@endphp
<div class="grid gap-4 px-4 py-3 md:grid-cols-2"
     data-translated="{{ $isTranslated ? '1' : '0' }}"
     x-show="!onlyUntranslated || !{{ $isTranslated ? 'true' : 'false' }}">
    <div class="min-w-0">
        <p class="mb-1 text-caption font-medium text-ink-500">{{ $label }}</p>
        @if (! empty(trim(strip_tags((string) $originalHtml))))
            <div class="text-body text-ink-900">{!! $originalHtml !!}</div>
        @else
            <p class="text-caption italic text-ink-500">—</p>
        @endif
    </div>
    <div class="min-w-0">
        <p class="mb-1 text-caption font-medium text-ink-500">{{ $label }}</p>
        @if ($multiline)
            <textarea name="{{ $inputName }}"
                      rows="3"
                      placeholder="{{ $placeholder }}"
                      class="w-full rounded-md border {{ $isTranslated ? 'border-line-200' : 'border-warning' }} bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">{{ strip_tags((string) $translationHtml) }}</textarea>
        @else
            <input type="text"
                   name="{{ $inputName }}"
                   value="{{ $translationHtml }}"
                   placeholder="{{ $placeholder }}"
                   class="w-full rounded-md border {{ $isTranslated ? 'border-line-200' : 'border-warning' }} bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
        @endif
    </div>
</div>
