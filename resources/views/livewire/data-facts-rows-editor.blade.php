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
 * Q4-Etappe 4 / G3 (2026-09-08): Row-Editor für den Fakten-Block.
 * Verwaltet das rows-JSON-Array (Label · Wert). Reorder, Add,
 * Remove, Inline-Edit — alles im Client-State, Persistierung
 * pro Änderung mit einem save() gegen den Block. Kein separater
 * Row-Save-Endpoint.
 *
 * Rows-Struktur: `[{label: {de: "...", en: "..."}, value: {de:
 * "...", en: "..."}}, …]`. Der Locale-Zugriff erfolgt beim
 * Anzeigen/Editieren via `app()->getLocale()`.
 *
 * Props:
 * - blockId (int) ID des DataFactBlock
 */
new class extends Component
{
    #[Locked]
    public int $blockId;

    /** @var array<int, array{label: array<string, string>, value: array<string, string>}> */
    public array $rows = [];

    public function mount(int $blockId): void
    {
        $this->blockId = $blockId;
        $block = DataFactBlock::findOrFail($blockId);
        $this->rows = $this->normalize($block->rows ?? []);
    }

    /**
     * Normalisiert die Row-Struktur — Legacy-Rows ohne Locale-Map
     * bekommen eine Default-Locale-Wrapping, damit der Editor sie
     * anfassen kann.
     *
     * @param  array<int, mixed>  $raw
     * @return array<int, array{label: array<string, string>, value: array<string, string>}>
     */
    private function normalize(array $raw): array
    {
        $locale = app()->getLocale();
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = $row['label'] ?? '';
            $value = $row['value'] ?? '';
            $out[] = [
                'label' => is_array($label) ? $label : [$locale => (string) $label],
                'value' => is_array($value) ? $value : [$locale => (string) $value],
            ];
        }

        return $out;
    }

    public function addRow(): void
    {
        $this->authorizeUpdate();
        $locale = app()->getLocale();
        $this->rows[] = ['label' => [$locale => ''], 'value' => [$locale => '']];
        $this->persist();
    }

    public function removeRow(int $index): void
    {
        $this->authorizeUpdate();
        if (! isset($this->rows[$index])) {
            return;
        }
        array_splice($this->rows, $index, 1);
        $this->persist();
    }

    public function moveUp(int $index): void
    {
        $this->authorizeUpdate();
        if ($index <= 0 || ! isset($this->rows[$index])) {
            return;
        }
        [$this->rows[$index - 1], $this->rows[$index]] = [$this->rows[$index], $this->rows[$index - 1]];
        $this->persist();
    }

    public function moveDown(int $index): void
    {
        $this->authorizeUpdate();
        if (! isset($this->rows[$index]) || $index >= count($this->rows) - 1) {
            return;
        }
        [$this->rows[$index], $this->rows[$index + 1]] = [$this->rows[$index + 1], $this->rows[$index]];
        $this->persist();
    }

    /**
     * Blur-Handler pro Zellen-Input. Livewire hält das gebundene
     * `rows[i][label][locale]` schon im State — hier nur noch
     * persistieren.
     */
    public function saveRow(): void
    {
        $this->authorizeUpdate();
        $this->persist();
    }

    private function persist(): void
    {
        $block = DataFactBlock::findOrFail($this->blockId);
        $block->rows = array_values($this->rows);
        $block->save();
    }

    private function authorizeUpdate(): void
    {
        $block = DataFactBlock::findOrFail($this->blockId);
        Gate::authorize('update', $block);
    }
};
?>

<div class="space-y-2">
    @php $locale = app()->getLocale(); @endphp
    @if (empty($rows))
        <p class="text-caption text-ink-500">
            {{ __('data_facts_empty_hint') }}
        </p>
    @else
        <ul class="divide-y divide-line-100 rounded-md border border-line-200 bg-paper-0">
            @foreach ($rows as $index => $row)
                <li class="grid grid-cols-1 items-start gap-2 p-2 md:grid-cols-[1fr_1fr_auto]">
                    <input
                        type="text"
                        wire:model.blur="rows.{{ $index }}.label.{{ $locale }}"
                        wire:change="saveRow"
                        placeholder="{{ __('data_facts_row_label_placeholder') }}"
                        class="block w-full rounded-md border border-line-200 bg-canvas-bg px-2 py-1.5 text-body text-ink-900"
                        aria-label="{{ __('data_facts_row_label') }}"
                    />
                    <input
                        type="text"
                        wire:model.blur="rows.{{ $index }}.value.{{ $locale }}"
                        wire:change="saveRow"
                        placeholder="{{ __('data_facts_row_value_placeholder') }}"
                        class="block w-full rounded-md border border-line-200 bg-canvas-bg px-2 py-1.5 text-body text-ink-900"
                        aria-label="{{ __('data_facts_row_value') }}"
                    />
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="moveUp({{ $index }})"
                                @disabled($index === 0)
                                title="{{ __('data_facts_row_move_up') }}"
                                class="inline-flex size-9 items-center justify-center rounded-md text-ink-600 hover:bg-line-100 hover:text-ink-900 disabled:opacity-30">
                            <x-icon name="arrow-up" size="4"/>
                        </button>
                        <button type="button" wire:click="moveDown({{ $index }})"
                                @disabled($index === count($rows) - 1)
                                title="{{ __('data_facts_row_move_down') }}"
                                class="inline-flex size-9 items-center justify-center rounded-md text-ink-600 hover:bg-line-100 hover:text-ink-900 disabled:opacity-30">
                            <x-icon name="arrow-down" size="4"/>
                        </button>
                        <button type="button"
                                wire:click="removeRow({{ $index }})"
                                onclick="return confirm('{{ __('data_facts_row_delete_confirm') }}')"
                                title="{{ __('data_facts_row_delete') }}"
                                class="inline-flex size-9 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger">
                            <x-icon name="trash-2" size="4"/>
                        </button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <button type="button"
            wire:click="addRow"
            class="inline-flex items-center gap-1.5 rounded-md border border-line-200 bg-paper-0 px-3 py-1.5 text-caption text-ink-900 hover:border-ink-400">
        <x-icon name="plus" size="4"/>
        {{ __('data_facts_row_add') }}
    </button>
</div>
