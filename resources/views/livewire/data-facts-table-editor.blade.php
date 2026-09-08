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
 * Q4-Etappe 4 / G3 (2026-09-08): Tabellen-Editor für den Fakten-
 * Block (Layout `tabelle`). Verwaltet Spalten-Definitionen und
 * N-Cell-Zeilen im `rows`-JSON.
 *
 * Spalten-Struktur: `columns = [{header: {de: "Jahr", en: "Year"}}, …]`
 * Row-Struktur: `rows = [{cells: [{de: "..."}, {de: "..."}, …]}, …]`
 * Zellen und Header sind je Locale-Map — durchgängig übersetzbar.
 */
new class extends Component
{
    #[Locked]
    public int $blockId;

    /** @var array<int, array{header: array<string, string>}> */
    public array $columns = [];

    /** @var array<int, array{cells: array<int, array<string, string>>}> */
    public array $rows = [];

    public function mount(int $blockId): void
    {
        $this->blockId = $blockId;
        $block = DataFactBlock::findOrFail($blockId);
        $this->columns = $this->normalizeColumns($block->columns ?? []);
        $this->rows = $this->normalizeRows($block->rows ?? [], count($this->columns));
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return array<int, array{header: array<string, string>}>
     */
    private function normalizeColumns(array $raw): array
    {
        $locale = app()->getLocale();
        $out = [];
        foreach ($raw as $col) {
            if (! is_array($col)) {
                continue;
            }
            $header = $col['header'] ?? '';
            $out[] = [
                'header' => is_array($header) ? $header : [$locale => (string) $header],
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return array<int, array{cells: array<int, array<string, string>>}>
     */
    private function normalizeRows(array $raw, int $colCount): array
    {
        $locale = app()->getLocale();
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cells = $row['cells'] ?? [];
            if (! is_array($cells)) {
                $cells = [];
            }
            // Auf die aktuelle Spalten-Anzahl bringen (Zellen anhängen
            // oder abschneiden). Beim Umschalten von steckbrief nach
            // tabelle können Zeilen ohne cells-Struktur vorkommen —
            // die werden hier auf leere N-Cell-Zeilen normalisiert.
            $normalized = [];
            for ($i = 0; $i < $colCount; $i++) {
                $cell = $cells[$i] ?? '';
                $normalized[] = is_array($cell) ? $cell : [$locale => (string) $cell];
            }
            $out[] = ['cells' => $normalized];
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Spalten
    // ------------------------------------------------------------------

    public function addColumn(): void
    {
        $this->authorizeUpdate();
        $locale = app()->getLocale();
        $this->columns[] = ['header' => [$locale => '']];
        // Alle bestehenden Zeilen um eine leere Zelle ergänzen.
        foreach ($this->rows as $i => $row) {
            $this->rows[$i]['cells'][] = [$locale => ''];
        }
        $this->persist();
    }

    public function removeColumn(int $index): void
    {
        $this->authorizeUpdate();
        if (! isset($this->columns[$index])) {
            return;
        }
        array_splice($this->columns, $index, 1);
        foreach ($this->rows as $i => $row) {
            if (isset($this->rows[$i]['cells'][$index])) {
                array_splice($this->rows[$i]['cells'], $index, 1);
            }
        }
        $this->persist();
    }

    // ------------------------------------------------------------------
    // Zeilen
    // ------------------------------------------------------------------

    public function addRow(): void
    {
        $this->authorizeUpdate();
        $locale = app()->getLocale();
        $cells = [];
        for ($i = 0; $i < count($this->columns); $i++) {
            $cells[] = [$locale => ''];
        }
        $this->rows[] = ['cells' => $cells];
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

    public function saveEdit(): void
    {
        $this->authorizeUpdate();
        $this->persist();
    }

    private function persist(): void
    {
        $block = DataFactBlock::findOrFail($this->blockId);
        $block->columns = array_values($this->columns);
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

<div class="space-y-3">
    @php $locale = app()->getLocale(); @endphp

    {{-- Spalten-Definitionen --}}
    <div>
        <p class="mb-1 text-caption font-medium text-ink-700">
            {{ __('data_facts_columns_label') }}
        </p>
        @if (empty($columns))
            <p class="text-caption text-ink-500">{{ __('data_facts_columns_empty_hint') }}</p>
        @else
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($columns as $ci => $col)
                    <div class="inline-flex items-center gap-1 rounded-md border border-line-200 bg-canvas-bg px-2 py-1">
                        <input type="text"
                               wire:model.blur="columns.{{ $ci }}.header.{{ $locale }}"
                               wire:change="saveEdit"
                               placeholder="{{ __('data_facts_column_header_placeholder') }}"
                               class="w-28 border-0 bg-transparent px-1 text-body text-ink-900 focus:outline-none"
                               aria-label="{{ __('data_facts_column_header_placeholder') }}"/>
                        <button type="button"
                                wire:click="removeColumn({{ $ci }})"
                                onclick="return confirm('{{ __('data_facts_column_delete_confirm') }}')"
                                title="{{ __('data_facts_column_delete') }}"
                                class="inline-flex size-7 items-center justify-center rounded text-ink-500 hover:bg-danger-bg hover:text-danger">
                            <x-icon name="x" size="4"/>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        <button type="button"
                wire:click="addColumn"
                class="mt-2 inline-flex items-center gap-1.5 rounded-md border border-line-200 bg-paper-0 px-3 py-1.5 text-caption text-ink-900 hover:border-ink-400">
            <x-icon name="plus" size="4"/>
            {{ __('data_facts_column_add') }}
        </button>
    </div>

    {{-- Zeilen (nur wenn Spalten definiert). --}}
    @if (! empty($columns))
        <div>
            <p class="mb-1 text-caption font-medium text-ink-700">
                {{ __('data_facts_rows_label') }}
            </p>
            @if (empty($rows))
                <p class="text-caption text-ink-500">{{ __('data_facts_empty_hint') }}</p>
            @else
                <div class="overflow-x-auto rounded-md border border-line-200">
                    <table class="min-w-full divide-y divide-line-100">
                        <thead class="bg-canvas-bg">
                            <tr>
                                @foreach ($columns as $col)
                                    <th class="px-2 py-1 text-left text-caption font-medium text-ink-700">
                                        {{ ($col['header'][$locale] ?? $col['header']['de'] ?? '') ?: '—' }}
                                    </th>
                                @endforeach
                                <th class="w-24 px-2 py-1"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-100 bg-paper-0">
                            @foreach ($rows as $ri => $row)
                                <tr>
                                    @foreach ($columns as $ci => $col)
                                        <td class="px-2 py-1">
                                            <input type="text"
                                                   wire:model.blur="rows.{{ $ri }}.cells.{{ $ci }}.{{ $locale }}"
                                                   wire:change="saveEdit"
                                                   class="block w-full rounded-md border border-line-200 bg-canvas-bg px-2 py-1 text-body text-ink-900"
                                                   aria-label="{{ ($col['header'][$locale] ?? $col['header']['de'] ?? '') }}"/>
                                        </td>
                                    @endforeach
                                    <td class="whitespace-nowrap px-2 py-1">
                                        <button type="button" wire:click="moveUp({{ $ri }})"
                                                @disabled($ri === 0)
                                                title="{{ __('data_facts_row_move_up') }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-ink-600 hover:bg-line-100 hover:text-ink-900 disabled:opacity-30">
                                            <x-icon name="arrow-up" size="4"/>
                                        </button>
                                        <button type="button" wire:click="moveDown({{ $ri }})"
                                                @disabled($ri === count($rows) - 1)
                                                title="{{ __('data_facts_row_move_down') }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-ink-600 hover:bg-line-100 hover:text-ink-900 disabled:opacity-30">
                                            <x-icon name="arrow-down" size="4"/>
                                        </button>
                                        <button type="button" wire:click="removeRow({{ $ri }})"
                                                onclick="return confirm('{{ __('data_facts_row_delete_confirm') }}')"
                                                title="{{ __('data_facts_row_delete') }}"
                                                class="inline-flex size-8 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger">
                                            <x-icon name="trash-2" size="4"/>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <button type="button"
                    wire:click="addRow"
                    class="mt-2 inline-flex items-center gap-1.5 rounded-md border border-line-200 bg-paper-0 px-3 py-1.5 text-caption text-ink-900 hover:border-ink-400">
                <x-icon name="plus" size="4"/>
                {{ __('data_facts_row_add') }}
            </button>
        </div>
    @endif
</div>
