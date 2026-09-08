<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\DataFactBlock;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 4 / G3 (2026-09-08): Layout-Umschalter (`steckbrief`
 * vs. `tabelle`) für den Fakten-Block. Inline speichern, ohne
 * Redirect — konsistent zum quote-kind-selector.
 */
new class extends Component
{
    #[Locked]
    public int $blockId;

    public string $value = DataFactBlock::LAYOUT_STECKBRIEF;

    public function mount(int $blockId, ?string $value = null): void
    {
        $this->blockId = $blockId;
        $this->value = in_array($value, [DataFactBlock::LAYOUT_STECKBRIEF, DataFactBlock::LAYOUT_TABELLE], true)
            ? $value
            : DataFactBlock::LAYOUT_STECKBRIEF;
    }

    public function save(): void
    {
        $block = DataFactBlock::findOrFail($this->blockId);
        Gate::authorize('update', $block);

        $block->layout = in_array($this->value, [DataFactBlock::LAYOUT_STECKBRIEF, DataFactBlock::LAYOUT_TABELLE], true)
            ? $this->value
            : DataFactBlock::LAYOUT_STECKBRIEF;
        $block->save();

        // Umschalten setzt die zum anderen Layout gehörenden Felder
        // absichtlich NICHT zurück — der Redakteur soll ohne Datenverlust
        // hin und her wechseln können, um „so oder so besser?"
        // vergleichen zu können.

        // Browser-Event für das Alpine-x-show-Umschalten in
        // data-facts-block. Damit reagiert die Editor-Ansicht
        // sofort — ohne Livewire-Rerender des Parent-Blocks.
        $this->dispatch(
            'data-facts-layout-changed',
            blockId: $this->blockId,
            layout: $block->layout,
        );
    }
};
?>

<fieldset class="flex items-center gap-4">
    <legend class="sr-only">{{ __('data_facts_layout_label') }}</legend>
    <label class="inline-flex items-center gap-2 text-caption text-ink-700">
        <input type="radio"
               wire:model.live="value"
               wire:change="save"
               value="{{ App\Models\DataFactBlock::LAYOUT_STECKBRIEF }}"/>
        <span>{{ __('data_facts_layout_steckbrief') }}</span>
    </label>
    <label class="inline-flex items-center gap-2 text-caption text-ink-700">
        <input type="radio"
               wire:model.live="value"
               wire:change="save"
               value="{{ App\Models\DataFactBlock::LAYOUT_TABELLE }}"/>
        <span>{{ __('data_facts_layout_tabelle') }}</span>
    </label>
</fieldset>
