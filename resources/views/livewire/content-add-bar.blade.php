<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\Entry;
use App\Services\ContentInsertionService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 4 / C1b (2026-09-08): Inline-Add-Bar zwischen Content-
 * Blöcken. Ersetzt die Bootstrap-Modal-Kette (contents/index.blade,
 * contents/gallery.blade, contents/audiovisual.blade) durch ein
 * inline sichtbares Trenner-Band mit Popover, das die drei aktuellen
 * Content-Typen (Text / Galerie / Audio-Video) anbietet — Bilder
 * bleiben Sache der Gallery-Dropzone.
 *
 * C1b liefert nur UI + Livewire-Dispatch. Die eigentliche Anlage
 * (POST auf text.store / save.gallery / save.audiovisual mit
 * sort_order) folgt in C1c.
 *
 * Props:
 * - entryId               (int)  Ziel-Entry
 * - afterMediaContentId   (?int) sort_order-Anker (null = vor dem
 *                                ersten Block)
 * - variant               (str)  „between" (Trenner zwischen zwei
 *                                Blöcken) oder „empty" (leerer
 *                                Entry — größere Fläche, dominanter
 *                                Trigger).
 * - entryName             (str)  Für den Popover-Kontext-Untertitel.
 * - position              (int)  1 = vor dem ersten Block, N = nach
 *                                Position (N-1). Nur Anzeige.
 */
new class extends Component
{
    #[Locked]
    public int $entryId;

    #[Locked]
    public ?int $afterMediaContentId = null;

    #[Locked]
    public string $variant = 'between';

    #[Locked]
    public string $entryName = '';

    #[Locked]
    public int $position = 1;

    public function mount(
        int $entryId,
        ?int $afterMediaContentId = null,
        string $variant = 'between',
        string $entryName = '',
        int $position = 1,
    ): void {
        $this->entryId = $entryId;
        $this->afterMediaContentId = $afterMediaContentId;
        $this->variant = in_array($variant, ['between', 'empty'], true) ? $variant : 'between';
        $this->entryName = $entryName;
        $this->position = max(1, $position);
    }

    /**
     * Legt einen leeren Block via ContentInsertionService an und
     * lädt die Editor-Seite neu, damit der neu entstandene Block
     * im DOM erscheint und sein In-Place-Editor geöffnet werden
     * kann. C1c ersetzt den vorherigen Event-Dispatch aus C1b.
     */
    public function requestAdd(string $type): void
    {
        if (! in_array($type, ['text', 'gallery', 'audiovisual', 'quote', 'data-facts'], true)) {
            return;
        }

        $entry = Entry::findOrFail($this->entryId);
        // Entry::project() ist keine Eloquent-Relation, sondern ein
        // Method-Access (gibt ?Project via Chapter zurück). Deshalb
        // Method-Call, nicht Property-Access.
        $project = $entry->project();

        // Gleicher Gate wie an allen bestehenden Content-Store-
        // Endpoints (siehe TextBlockController::saveText).
        Gate::authorize('update', $entry);

        $newMediaId = app(ContentInsertionService::class)
            ->insertBlank($this->entryId, $type, $this->afterMediaContentId);

        // Livewire-SPA-Swap zurück zum Editor (kein Full-Reload,
        // damit die Seite bei großen Entries nicht sichtbar durchs
        // Scrolling läuft). Der `livewire:navigated`-Listener im
        // components/layout.blade.php scrollt nach dem Swap
        // instant zum Anchor.
        $target = route('projects.edit', $project->id)
            .'#anchor_MediaContent_'.$newMediaId;

        $this->redirect($target, navigate: true);
    }
};
?>

{{-- Alpine übernimmt das Open/Close, damit kein Livewire-Roundtrip
     pro Klick nötig ist. Das Popover ist ein role="dialog" mit
     Focus-Trap: Tab zirkuliert nur innerhalb, ESC schließt und
     springt zurück auf den Trigger. --}}
<div
    class="content-add-bar {{ $variant === 'empty' ? 'my-2' : 'my-1' }}"
    x-data="{
        open: false,
        options: ['text', 'gallery', 'audiovisual', 'quote', 'data-facts'],
        activeIndex: 0,
        toggle() { this.open ? this.close() : this.openMenu(); },
        openMenu() {
            this.open = true;
            this.activeIndex = 0;
            this.$nextTick(() => this.focusOption(0));
        },
        close() {
            this.open = false;
            this.$nextTick(() => this.$refs.trigger?.focus());
        },
        focusOption(i) {
            this.activeIndex = ((i % this.options.length) + this.options.length) % this.options.length;
            const el = this.$refs.menu?.querySelectorAll('[role=menuitem]')[this.activeIndex];
            el?.focus();
        },
        onKeyMenu(e) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); this.focusOption(this.activeIndex + 1); }
            else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); this.focusOption(this.activeIndex - 1); }
            else if (e.key === 'Home') { e.preventDefault(); this.focusOption(0); }
            else if (e.key === 'End') { e.preventDefault(); this.focusOption(this.options.length - 1); }
        },
    }"
    @keydown.escape.window="if (open) close()"
    @click.outside="if (open) close()"
>
    {{-- Trenner-Band: dezent sichtbar (opacity-40), Hover/Focus voll.
         In der `between`-Variante wird der Text „Hier einfügen"
         sichtbar (Design-Briefing v4, Screen 05·5); in `empty`
         steht der volle „Neuer Inhalt"-Trigger im Feld. --}}
    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="menu"
        aria-label="{{ $variant === 'between' ? __('add_bar_between_label') : __('add_bar_trigger') }}"
        @if ($variant === 'between')
            class="group flex w-full items-center justify-center gap-2 rounded-md
                   border border-dashed border-primary/40
                   py-1.5 text-caption text-primary/70 transition-colors duration-150
                   hover:border-primary hover:bg-primary/5 hover:text-primary
                   focus-visible:border-primary focus-visible:text-primary
                   focus-visible:outline focus-visible:outline-2
                   focus-visible:outline-offset-2 focus-visible:outline-primary
                   data-[open]:border-primary data-[open]:text-primary"
        @else
            class="group flex w-full items-center justify-center gap-2 rounded-md
                   border border-dashed border-line-200 py-4
                   text-caption text-ink-500 opacity-60 transition-opacity duration-150
                   hover:opacity-100 hover:border-ink-400 hover:text-ink-700
                   focus-visible:opacity-100 focus-visible:outline
                   focus-visible:outline-2 focus-visible:outline-offset-2
                   focus-visible:outline-primary
                   data-[open]:opacity-100"
        @endif
        :data-open="open ? 'true' : null"
    >
        <x-icon name="plus" size="4"/>
        <span>
            {{ $variant === 'between' ? __('add_bar_between_label') : __('add_bar_trigger') }}
        </span>
        @if ($variant === 'between')
            <x-icon name="plus" size="4"/>
        @endif
    </button>

    {{-- Popover / Menu — Slide-Fade + max-height wie Kommentar-Panel.
         Outer wrapper animiert nur max-height/opacity/translate und
         hat weder Padding noch Border, damit `max-h-0` tatsächlich
         auf 0px kollabiert. Inner wrapper trägt Padding + Border. --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="motion-safe:transition-all"
        x-transition:enter-start="opacity-0 -translate-y-2 max-h-0"
        x-transition:enter-end="opacity-100 translate-y-0 max-h-96"
        x-transition:leave="motion-safe:transition-all"
        x-transition:leave-start="opacity-100 translate-y-0 max-h-96"
        x-transition:leave-end="opacity-0 -translate-y-2 max-h-0"
        style="transition-duration: 350ms; transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);"
        x-ref="menu"
        role="menu"
        aria-label="{{ __('add_bar_title') }}"
        @keydown="onKeyMenu($event)"
        class="overflow-hidden"
    >
        <div class="mt-1 rounded-md border border-line-200 bg-paper-0 p-3 shadow-md">
        @if ($entryName !== '')
            <p class="mb-2 text-center text-caption text-ink-500">
                @if ($afterMediaContentId === null)
                    {{ __('add_bar_context_top', ['entry' => $entryName]) }}
                @else
                    {{ __('add_bar_context_after', ['entry' => $entryName, 'position' => $position - 1]) }}
                @endif
            </p>
        @endif
        <div class="flex flex-wrap items-stretch justify-center gap-2">
        <button
            type="button"
            role="menuitem"
            wire:click="requestAdd('text')"
            @click="close()"
            class="flex min-w-[7rem] flex-col items-center gap-1 rounded-md
                   border border-line-200 bg-canvas-bg px-3 py-2
                   text-caption text-ink-900
                   hover:border-primary hover:bg-primary/5
                   focus-visible:outline focus-visible:outline-2
                   focus-visible:outline-offset-2 focus-visible:outline-primary"
        >
            <x-icon name="file-font" size="5"/>
            <span>{{ __('add_bar_option_text') }}</span>
        </button>
        <button
            type="button"
            role="menuitem"
            wire:click="requestAdd('gallery')"
            @click="close()"
            class="flex min-w-[7rem] flex-col items-center gap-1 rounded-md
                   border border-line-200 bg-canvas-bg px-3 py-2
                   text-caption text-ink-900
                   hover:border-primary hover:bg-primary/5
                   focus-visible:outline focus-visible:outline-2
                   focus-visible:outline-offset-2 focus-visible:outline-primary"
        >
            <x-icon name="file-image" size="5"/>
            <span>{{ __('add_bar_option_gallery') }}</span>
        </button>
        <button
            type="button"
            role="menuitem"
            wire:click="requestAdd('audiovisual')"
            @click="close()"
            class="flex min-w-[7rem] flex-col items-center gap-1 rounded-md
                   border border-line-200 bg-canvas-bg px-3 py-2
                   text-caption text-ink-900
                   hover:border-primary hover:bg-primary/5
                   focus-visible:outline focus-visible:outline-2
                   focus-visible:outline-offset-2 focus-visible:outline-primary"
        >
            <x-icon name="camera-video" size="5"/>
            <span>{{ __('add_bar_option_audiovisual') }}</span>
        </button>
        <button
            type="button"
            role="menuitem"
            wire:click="requestAdd('quote')"
            @click="close()"
            class="flex min-w-[7rem] flex-col items-center gap-1 rounded-md
                   border border-line-200 bg-canvas-bg px-3 py-2
                   text-caption text-ink-900
                   hover:border-primary hover:bg-primary/5
                   focus-visible:outline focus-visible:outline-2
                   focus-visible:outline-offset-2 focus-visible:outline-primary"
        >
            <x-icon name="quote" size="5"/>
            <span>{{ __('add_bar_option_quote') }}</span>
        </button>
        <button
            type="button"
            role="menuitem"
            wire:click="requestAdd('data-facts')"
            @click="close()"
            class="flex min-w-[7rem] flex-col items-center gap-1 rounded-md
                   border border-line-200 bg-canvas-bg px-3 py-2
                   text-caption text-ink-900
                   hover:border-primary hover:bg-primary/5
                   focus-visible:outline focus-visible:outline-2
                   focus-visible:outline-offset-2 focus-visible:outline-primary"
        >
            <x-icon name="table" size="5"/>
            <span>{{ __('add_bar_option_data_facts') }}</span>
        </button>
        </div>
        </div>
    </div>
</div>
