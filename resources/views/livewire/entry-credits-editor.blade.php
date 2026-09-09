<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\Entry;
use App\Models\EntryCredit;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 5 / G-Fund-5 (2026-09-09): Editor für Abschnitts-
 * Credits. Redakteur trägt Namen mit Rolle (Recherche / Redaktion /
 * Hinweis) und optional einem Datum ein. Live-Save pro Änderung.
 *
 * Props:
 *  - entryId (int)
 */
new class extends Component
{
    #[Locked]
    public int $entryId;

    /** @var array<int, array{id:?int, role:string, name:string, date:?string}> */
    public array $rows = [];

    public function mount(int $entryId): void
    {
        $this->entryId = $entryId;
        $entry = Entry::findOrFail($entryId);
        $this->rows = $entry->credits
            ->map(fn (EntryCredit $c) => [
                'id' => $c->id,
                'role' => $c->role,
                'name' => $c->name,
                'date' => optional($c->date)->format('Y-m-d'),
            ])
            ->all();
    }

    public function addRow(): void
    {
        $this->authorizeEntry();
        $this->rows[] = ['id' => null, 'role' => EntryCredit::ROLE_RECHERCHE, 'name' => '', 'date' => null];
    }

    public function removeRow(int $index): void
    {
        $this->authorizeEntry();
        if (! isset($this->rows[$index])) {
            return;
        }
        $rowId = $this->rows[$index]['id'] ?? null;
        if ($rowId !== null) {
            EntryCredit::query()->whereKey($rowId)->delete();
        }
        array_splice($this->rows, $index, 1);
    }

    public function saveRow(int $index): void
    {
        $this->authorizeEntry();
        if (! isset($this->rows[$index])) {
            return;
        }
        $row = $this->rows[$index];

        if (! in_array($row['role'], EntryCredit::ROLES, true)) {
            return;
        }
        if (trim((string) $row['name']) === '') {
            return;
        }

        if (! empty($row['id'])) {
            $credit = EntryCredit::query()->findOrFail($row['id']);
            $credit->update([
                'role' => $row['role'],
                'name' => trim((string) $row['name']),
                'date' => $row['date'] ?: null,
                'position' => $index,
            ]);
        } else {
            $credit = EntryCredit::create([
                'entry_id' => $this->entryId,
                'role' => $row['role'],
                'name' => trim((string) $row['name']),
                'date' => $row['date'] ?: null,
                'position' => $index,
            ]);
            $this->rows[$index]['id'] = $credit->id;
        }
    }

    private function authorizeEntry(): void
    {
        $entry = Entry::findOrFail($this->entryId);
        Gate::authorize('update', $entry);
    }
};
?>

<div class="space-y-2">
    @if (empty($rows))
        <p class="text-caption text-ink-500">
            {{ __('entry_credits_empty_hint') }}
        </p>
    @else
        <ul class="divide-y divide-line-100 rounded-md border border-line-200 bg-paper-0">
            @foreach ($rows as $index => $row)
                <li class="grid grid-cols-1 items-start gap-2 p-2 md:grid-cols-[10rem_1fr_10rem_auto]">
                    <select wire:model.blur="rows.{{ $index }}.role"
                            wire:change="saveRow({{ $index }})"
                            class="block w-full rounded-md border border-line-200 bg-canvas-bg px-2 py-1.5 text-body">
                        <option value="recherche">{{ __('entry_credit_role_recherche') }}</option>
                        <option value="redaktion">{{ __('entry_credit_role_redaktion') }}</option>
                        <option value="hinweis">{{ __('entry_credit_role_hinweis') }}</option>
                    </select>
                    <input type="text"
                           wire:model.blur="rows.{{ $index }}.name"
                           wire:change="saveRow({{ $index }})"
                           placeholder="{{ __('entry_credit_name_placeholder') }}"
                           class="block w-full rounded-md border border-line-200 bg-canvas-bg px-2 py-1.5 text-body text-ink-900"/>
                    <input type="month"
                           wire:model.blur="rows.{{ $index }}.date"
                           wire:change="saveRow({{ $index }})"
                           class="block w-full rounded-md border border-line-200 bg-canvas-bg px-2 py-1.5 text-body"/>
                    <button type="button"
                            wire:click="removeRow({{ $index }})"
                            onclick="return confirm('{{ __('entry_credits_delete_confirm') }}')"
                            title="{{ __('entry_credits_delete') }}"
                            class="inline-flex size-9 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger">
                        <x-icon name="trash-2" size="4"/>
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    <button type="button"
            wire:click="addRow"
            class="inline-flex items-center gap-1.5 rounded-md border border-line-200 bg-paper-0 px-3 py-1.5 text-caption text-ink-900 hover:border-ink-400">
        <x-icon name="plus" size="4"/>
        {{ __('entry_credits_add') }}
    </button>
</div>
