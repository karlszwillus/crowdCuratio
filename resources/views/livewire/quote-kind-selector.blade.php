<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\QuoteBlock;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 4 / F3 (2026-09-08): Inline-Selector für das `kind`-Feld
 * am Zitat-Block. Ersetzt das klassische `<select onchange="submit">`
 * + `quote.update-kind`-Redirect-Round durch einen Livewire-Change,
 * damit die UX zur restlichen Inline-Editor-Kette (Text, Speaker,
 * Source) konsistent ist und der Browser nicht scrollen muss.
 *
 * Props:
 * - quoteId  ID des QuoteBlock
 * - value    aktueller Wert (`archivalie` / `interview` / `publikation`
 *            oder null)
 */
new class extends Component
{
    #[Locked]
    public int $quoteId;

    public ?string $value = null;

    public function mount(int $quoteId, ?string $value = null): void
    {
        $this->quoteId = $quoteId;
        $this->value = in_array($value, ['archivalie', 'interview', 'publikation'], true)
            ? $value
            : null;
    }

    public function save(): void
    {
        $quote = QuoteBlock::findOrFail($this->quoteId);
        Gate::authorize('update', $quote);

        $quote->kind = in_array($this->value, ['archivalie', 'interview', 'publikation'], true)
            ? $this->value
            : null;
        $quote->save();
    }
};
?>

<select
    wire:model.live="value"
    wire:change="save"
    class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body"
    aria-label="{{ __('quote_kind') }}"
>
    <option value="">—</option>
    <option value="archivalie">{{ __('quote_kind_archivalie') }}</option>
    <option value="interview">{{ __('quote_kind_interview') }}</option>
    <option value="publikation">{{ __('quote_kind_publikation') }}</option>
</select>
