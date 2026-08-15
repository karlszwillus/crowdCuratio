<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| UI-Komponenten-Bibliothek (Phase 5)
|--------------------------------------------------------------------------
|
| Render-Tests für die sechs anonymen Blade-Komponenten unter
| resources/views/components/ui/. Geprüft werden Variant-Klassen,
| Pflicht-ARIA-Attribute und Slot-Durchreichung. Keine DB, keine
| Browser-Asserts.
*/

// ---------- Button ----------

it('Button rendert mit Default-Variant primary', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.button>Speichern</x-ui.button>');

    expect($html)
        ->toContain('<button')
        ->toContain('type="button"')
        ->toContain('bg-primary')
        ->toContain('text-primary-on')
        ->toContain('Speichern');
});

it('Button rendert Danger-Variant mit roter Flaeche', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.button variant="danger">Löschen</x-ui.button>');

    expect($html)->toContain('bg-danger');
    expect(str_contains($html, 'bg-primary'))->toBeFalse();
});

it('Button mit disabled setzt aria-disabled', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.button :disabled="true">Aus</x-ui.button>');

    expect($html)
        ->toContain('disabled')
        ->toContain('aria-disabled="true"');
});

// ---------- Locked-Pattern (Phase 5d.1) ----------
//
// Unterschied disabled vs. locked:
//   disabled  = native HTML-`disabled`, Fokus geht verloren, kein Klick,
//               kein Tooltip lesbar. Fuer Formular-States.
//   locked    = sichtbar, fokussierbar (aria-disabled=true), NICHT
//               nativ disabled. Tooltip erklaert warum. Schloss-Icon
//               links vom Label. Fuer rollen-bedingte Sperren, damit
//               Reader/Editor-User verstehen, was sie NICHT duerfen.
//
// Persona-Befund B-K-B-04 (2026-06-23): Reader sieht Add-User-Button
// gar nicht → sucht ihn und ist verwirrt. Fix: sichtbar, aber locked.

it('Button mit locked rendert aria-disabled ohne natives disabled', function () {
    /** @var TestCase $this */
    $html = Blade::render(
        '<x-ui.button :locked="true" lockedReason="Nur Editor:innen">Hinzufuegen</x-ui.button>'
    );

    // @phpstan-ignore-next-line property.notFound (Pest-Magic ->not->)
    expect($html)
        ->toContain('aria-disabled="true"')
        ->not->toContain(' disabled ')
        ->not->toContain('disabled>');
});

it('Button mit locked setzt Schloss-Icon links vom Label', function () {
    /** @var TestCase $this */
    $htmlLocked = Blade::render(
        '<x-ui.button :locked="true" lockedReason="Nur Editor:innen">Hinzufuegen</x-ui.button>'
    );
    $htmlOpen = Blade::render(
        '<x-ui.button>Hinzufuegen</x-ui.button>'
    );

    // Delta-Vergleich: der locked-Button hat ein zusaetzliches SVG,
    // das im offenen Button nicht drin ist. Wir pruefen die Praesenz
    // eines <svg-Elements im locked-Rendering, das im offenen fehlt.
    expect($htmlLocked)->toContain('<svg');
    expect(substr_count($htmlLocked, '<svg'))->toBeGreaterThan(substr_count($htmlOpen, '<svg'));

    // Reihenfolge im Markup: SVG steht VOR dem Label.
    $iconPos = strpos($htmlLocked, '<svg');
    $labelPos = strpos($htmlLocked, 'Hinzufuegen');
    expect($iconPos)->toBeInt()->toBeLessThan($labelPos);
});

it('Button mit locked setzt title-Attribut aus lockedReason', function () {
    /** @var TestCase $this */
    $html = Blade::render(
        '<x-ui.button :locked="true" lockedReason="Nur Editor:innen duerfen einladen">'
        .'Nutzer:in einladen</x-ui.button>'
    );

    expect($html)->toContain('title="Nur Editor:innen duerfen einladen"');
});

it('Button mit locked haengt is-disabled-Klasse an', function () {
    /** @var TestCase $this */
    $html = Blade::render(
        '<x-ui.button :locked="true" lockedReason="Nur Editor:innen">Hinzufuegen</x-ui.button>'
    );

    // is-disabled ist der Style-Anker fuer 5d — abdimmen,
    // cursor:not-allowed, ohne den nativen disabled-Pfad zu triggern.
    expect($html)->toContain('is-disabled');
});

// ---------- @disabledIf-Direktive (Phase 5d.2) ----------
//
// Ergaenzung zur <x-ui.button :locked>-Prop: einige Bestands-Buttons
// leben als rohe <button>-Tags in Legacy-Templates. Fuer die ist ein
// Blade-Direktive-Sprue der schnellste Weg zum Locked-Zustand,
// ohne die Komponente zu tauschen.
//
// Nutzung:
//   <button @disabledIf(! $canAdd, 'Nur Editor:innen') class="...">
// wird zu:
//   <button aria-disabled="true" title="..." class="is-disabled ...">
//
// Wenn Condition false: die Direktive schreibt nichts (Button bleibt
// offen). Kein Schloss-Icon in dieser Variante — bewusst schlank,
// fuer die volle Behandlung bleibt <x-ui.button :locked>.

it('@disabledIf mit true-Condition schreibt aria-disabled, title und data-locked', function () {
    /** @var TestCase $this */
    $html = Blade::render(
        '<button @disabledIf(true, "Nur Editor:innen") class="btn">Add</button>'
    );

    // data-locked="1" statt is-disabled-Klasse — die Direktive kann
    // keinen class-String in einen bestehenden class="..." Wert des
    // umgebenden Tags mergen. CSS-Regel matcht beide Anker.
    expect($html)
        ->toContain('aria-disabled="true"')
        ->toContain('title="Nur Editor:innen"')
        ->toContain('data-locked="1"');
});

it('@disabledIf mit false-Condition rendert einen offenen Button', function () {
    /** @var TestCase $this */
    $html = Blade::render(
        '<button @disabledIf(false, "irgendwas") class="btn">Add</button>'
    );

    // @phpstan-ignore-next-line property.notFound (Pest-Magic ->not->)
    expect($html)
        ->not->toContain('aria-disabled')
        ->not->toContain('title=')
        ->not->toContain('data-locked');
});

it('@disabledIf escaped den reason gegen HTML-Injection', function () {
    /** @var TestCase $this */
    // Reason kommt oft aus Uebersetzungs-Strings, sollte aber trotzdem
    // sauber geescaped werden falls dynamisch (User-Input, DB-Wert).
    $html = Blade::render(
        '<button @disabledIf(true, $reason) class="btn">Add</button>',
        ['reason' => 'Nur "Editor:innen" & Admins']
    );

    expect($html)
        ->toContain('title="Nur &quot;Editor:innen&quot; &amp; Admins"');
});

// ---------- Save-Bar (Phase 5d.5) ----------
//
// Sticky-Sicherungs-Leiste am unteren Rand des Content-Bereichs.
// Zeigt sich, wenn der umgebende Alpine-Scope einen isDirty-Boolean
// auf true schaltet — mit Save- und optionalem Discard-Button.
//
// Karl-Entscheidung 2026-08-15 (5d.5): expliziter Save-Button,
// nicht Undo-Toast. Wird von der Permission-Sicht (5d.4) genutzt,
// und ist als Muster auch fuer andere „Batch-Aenderung"-Flows
// vorbereitet.

it('Save-Bar rendert Save-Button mit Alpine-Klick-Bindung', function () {
    /** @var TestCase $this */
    $html = Blade::render(
        '<x-ui.save-bar dirty-expr="isDirty" save-expr="save()"/>'
    );

    // x-show="isDirty" bindet Sichtbarkeit ans Parent-Alpine-Scope
    expect($html)
        ->toContain('x-show="isDirty"')
        ->toContain('@click="save()"')
        ->toContain('aria-label');
});

it('Save-Bar rendert Discard-Button wenn discard-expr gesetzt ist', function () {
    /** @var TestCase $this */
    $html = Blade::render(
        '<x-ui.save-bar dirty-expr="isDirty" save-expr="save()" discard-expr="reset()"/>'
    );

    expect($html)
        ->toContain('@click="reset()"');
});

it('Save-Bar ohne discard-expr rendert nur den Save-Button', function () {
    /** @var TestCase $this */
    $html = Blade::render(
        '<x-ui.save-bar dirty-expr="isDirty" save-expr="save()"/>'
    );

    // Es darf nur ein @click drin sein — der auf save().
    expect(substr_count($html, '@click='))->toBe(1);
});

// ---------- Icon-Button ----------

it('Icon-Button erzwingt label und setzt aria-label', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.icon-button label="Schließen">X</x-ui.icon-button>');

    expect($html)
        ->toContain('aria-label="Schließen"')
        ->toContain('h-11 w-11'); // WCAG 2.2 Target 44px
});

it('Icon-Button ohne label wirft Exception', function () {
    /** @var TestCase $this */
    Blade::render('<x-ui.icon-button>X</x-ui.icon-button>');
})->throws(ViewException::class, 'icon-button benötigt das Pflicht-Prop');

// ---------- Input ----------

it('Input rendert Label + Input und verknuepft sie via for/id', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.input name="email" label="E-Mail"/>');

    expect($html)
        ->toContain('<label for="email"')
        ->toContain('id="email"')
        ->toContain('name="email"')
        ->toContain('E-Mail');
});

it('Input mit required zeigt Stern + sr-only Pflichtfeld-Hinweis', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.input name="name" label="Name" :required="true"/>');

    expect($html)
        ->toContain('required')
        ->toContain('aria-required="true"')
        ->toContain('text-danger')
        ->toContain('sr-only')
        ->toContain('*');
});

it('Input mit error setzt aria-invalid und role=alert', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.input name="email" label="E-Mail" error="Ungültige Adresse"/>');

    expect($html)
        ->toContain('aria-invalid="true"')
        ->toContain('aria-describedby="email-error"')
        ->toContain('role="alert"')
        ->toContain('Ungültige Adresse');
});

// ---------- Toggle ----------

it('Toggle rendert als role=switch mit Alpine-Bindung', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.toggle label="Dunkelmodus"/>');

    expect($html)
        ->toContain('role="switch"')
        ->toContain('aria-label="Dunkelmodus"')
        ->toContain('x-data')
        ->toContain('@click')
        ->toContain('@keydown.space');
});

it('Toggle mit name fuegt Hidden-Input fuer Form-Submit hinzu', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.toggle label="Aktiv" name="active" :checked="true"/>');

    expect($html)
        ->toContain('type="hidden"')
        ->toContain('name="active"');
});

it('Toggle Off-Track hat einen Inset-Border als zweiten visuellen Kanal', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.toggle label="Aktiv"/>');

    // Mikro-Schaerfung aus v3-Review: Off-State muss neben der Farbe
    // einen zweiten visuellen Kanal haben.
    expect($html)->toContain('shadow-[inset_0_0_0_1px_var(--color-ink-700)]');
});

it('Toggle ohne label wirft Exception', function () {
    /** @var TestCase $this */
    Blade::render('<x-ui.toggle/>');
})->throws(ViewException::class, 'toggle benötigt das Pflicht-Prop');

// ---------- Card ----------

it('Card rendert mit Default-Variant inhalt', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.card>Inhalt-Text</x-ui.card>');

    expect($html)
        ->toContain('<section')
        ->toContain('border-ink-400') // inhalt-Variant
        ->toContain('Inhalt-Text');
});

it('Card mit Variant chapter rendert kraeftigere Border + Titel', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.card variant="chapter" title="Kapitel 1">Body</x-ui.card>');

    expect($html)
        ->toContain('border-2')
        ->toContain('border-ink-900')
        ->toContain('<h2')
        ->toContain('Kapitel 1');
});

it('Card kann Heading-Level ueberschreiben', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.card title="Heading" :headingLevel="3">Body</x-ui.card>');

    expect($html)->toContain('<h3');
});

// ---------- Banner ----------

it('Banner Default rendert mit role=status und aria-live=polite', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.banner>Hinweis-Text</x-ui.banner>');

    expect($html)
        ->toContain('role="status"')
        ->toContain('aria-live="polite"')
        ->toContain('bg-info-bg')
        ->toContain('Hinweis-Text');
});

it('Banner Type danger nutzt assertive Live-Region und role=alert', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.banner type="danger" title="Fehler">Details</x-ui.banner>');

    expect($html)
        ->toContain('role="alert"')
        ->toContain('aria-live="assertive"')
        ->toContain('bg-danger-bg')
        ->toContain('Fehler')
        ->toContain('Details');
});

it('Banner mit dismissible rendert Close-Button mit aria-label', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.banner type="info" :dismissible="true">Schliessbar</x-ui.banner>');

    expect($html)
        ->toContain('aria-label="Schließen"')
        ->toContain('Schliessbar');
});

// ---------- Icon (Lucide via <x-icon>, Phase 5-D.2) ----------

it('Icon rendert Lucide-SVG mit aria-hidden default', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-icon name="check"/>');

    expect($html)
        ->toContain('<svg')
        ->toContain('aria-hidden="true"')
        ->toContain('focusable="false"');
});

it('Icon in decorative=false-Modus rendert role=img und aria-label', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-icon name="pencil" :decorative="false" label="Bearbeiten"/>');

    expect($html)
        ->toContain('role="img"')
        ->toContain('aria-label="Bearbeiten"');
});

it('Icon mappt bi-*-Altnamen ueber icon-mapping.php auf Lucide', function () {
    /** @var TestCase $this */
    // `bi-trash` steht in config/icon-mapping.php auf `trash-2`.
    // Renders wird ueber die blade-lucide-icons-Komponente
    // `lucide-trash-2` aufgeloest — SVG-Output enthaelt die Lucide-
    // typische viewBox 0 0 24 24 und die stroke-Attribute.
    $html = Blade::render('<x-icon name="bi-trash"/>');

    expect($html)->toContain('<svg');
    expect($html)->toContain('viewBox="0 0 24 24"');
});

// ---------- Modal ----------

it('Modal rendert mit .modal-Klasse fuer den Vanilla-Modal-Manager', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.modal id="testModal">Body-Inhalt</x-ui.modal>');

    expect($html)
        ->toContain('class="modal fade"')
        ->toContain('id="testModal"')
        ->toContain('role="dialog"')
        ->toContain('aria-hidden="true"')
        ->toContain('Body-Inhalt');
});

it('Modal rendert aria-modal="true" und Focus-Trap-Alpine-Attribute', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.modal id="x">Body</x-ui.modal>');

    // Focus-Trap via @alpinejs/focus. Open-State reagiert auf die
    // custom Events cc-modal-shown/cc-modal-hidden aus modal.js.
    expect($html)
        ->toContain('aria-modal="true"')
        ->toContain('x-data="{ open: false }"')
        ->toContain('@cc-modal-shown="open = true"')
        ->toContain('@cc-modal-hidden="open = false"')
        ->toContain('x-trap.noscroll.inert="open"');
});

it('Modal mit title rendert Heading und verknuepft via aria-labelledby', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.modal id="x" title="Inhalt anlegen">Body</x-ui.modal>');

    expect($html)
        ->toContain('aria-labelledby="x-title"')
        ->toContain('id="x-title"')
        ->toContain('<h2')
        ->toContain('Inhalt anlegen');
});

it('Modal mit closable=true rendert dismiss-Button mit data-dismiss', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.modal id="x">Body</x-ui.modal>');

    expect($html)
        ->toContain('data-dismiss="modal"')
        ->toContain('aria-label="')
        ->toContain('&times;');
});

it('Modal mit closable=false rendert keinen dismiss-Button', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.modal id="x" :closable="false">Body</x-ui.modal>');

    expect(str_contains($html, 'data-dismiss="modal"'))->toBeFalse();
});

it('Modal size=lg bekommt max-w-4xl', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.modal id="x" size="lg">Body</x-ui.modal>');

    expect($html)->toContain('max-w-4xl');
});

it('Modal mit Footer-Slot rendert <footer>', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.modal id="x">Body<x-slot:footer>Save-Button</x-slot:footer></x-ui.modal>');

    expect($html)
        ->toContain('<footer')
        ->toContain('Save-Button');
});

it('Modal ohne id wirft Exception', function () {
    /** @var TestCase $this */
    Blade::render('<x-ui.modal>Body</x-ui.modal>');
})->throws(ViewException::class, 'x-ui.modal benötigt das Pflicht-Prop');

// ---------- Breadcrumb ----------

it('Breadcrumb rendert <nav aria-label="Breadcrumb"> mit <ol>', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.breadcrumb :items="[[\'label\' => \'Start\', \'href\' => \'#\']]" />');

    expect($html)
        ->toContain('<nav aria-label="Breadcrumb"')
        ->toContain('<ol')
        ->toContain('Start');
});

it('Breadcrumb markiert den letzten Eintrag mit aria-current="page" und ohne Link', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.breadcrumb :items="[
        [\'label\' => \'Projekt\', \'href\' => \'#main-content\'],
        [\'label\' => \'Kapitel 1\', \'href\' => \'#anchor_Chapter_1\'],
    ]" />');

    expect($html)
        ->toContain('href="#main-content"')
        ->toContain('aria-current="page"')
        ->toContain('Kapitel 1');

    // Der letzte Eintrag darf nicht als Link gerendert sein.
    expect($html)->not->toContain('href="#anchor_Chapter_1"');
});

it('Breadcrumb mit leerem items-Array rendert kein <nav>', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.breadcrumb :items="[]" />');

    expect($html)->not->toContain('<nav');
});

it('Breadcrumb rendert Trenner zwischen Einträgen, aber nicht nach dem letzten', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.breadcrumb :items="[
        [\'label\' => \'A\', \'href\' => \'#a\'],
        [\'label\' => \'B\', \'href\' => \'#b\'],
        [\'label\' => \'C\', \'href\' => \'#c\'],
    ]" />');

    // Zwei Trenner-Slashes bei drei Einträgen.
    expect(substr_count($html, 'aria-hidden="true"'))->toBe(2);
});

it('Breadcrumb im :tree-Modus rendert Alpine-Wrapper mit hashchange-Listener', function () {
    /** @var TestCase $this */
    $tree = [
        'root' => ['label' => 'Projekt X', 'href' => '#main-content'],
        'chapters' => [
            1 => [
                'label' => 'Kapitel A',
                'href' => '#anchor_Chapter_1',
                'entries' => [
                    7 => ['label' => 'Abschnitt Z', 'href' => '#anchor_Entry_7'],
                ],
            ],
        ],
    ];

    $html = Blade::render('<x-ui.breadcrumb :tree="$tree" />', ['tree' => $tree]);

    expect($html)
        ->toContain('x-data="ccBreadcrumb(')
        ->toContain('x-init="syncFromHash()"')
        ->toContain('@hashchange.window="syncFromHash()"')
        ->toContain('<nav aria-label="Breadcrumb"')
        // Tree-Daten landen als JSON inline im x-data-Aufruf.
        ->toContain('Projekt X')
        ->toContain('Kapitel A')
        ->toContain('Abschnitt Z');
});

it('Breadcrumb bevorzugt :tree gegenüber :items, wenn beide gesetzt', function () {
    /** @var TestCase $this */
    $tree = ['root' => ['label' => 'Tree-Root', 'href' => '#x'], 'chapters' => []];
    $items = [['label' => 'Static-Item', 'href' => '#y']];

    $html = Blade::render(
        '<x-ui.breadcrumb :tree="$tree" :items="$items" />',
        ['tree' => $tree, 'items' => $items]
    );

    // @phpstan-ignore-next-line property.notFound (Pest-Magic ->not->)
    expect($html)
        ->toContain('x-data="ccBreadcrumb(')
        ->not->toContain('Static-Item');
});

// ---------- Block-Card (Phase 5-D.6) ----------

it('Block-Card rendert Typ-Tag mit Icon und Label', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.block-card type="text">Inhalt</x-ui.block-card>');

    // Typ-Tag ist ein Pill (rounded-full) mit Lucide-Icon als Inline-
    // SVG plus dem lokalisierten Label. Fuer type="text" ist das der
    // Sprachschluessel block_type_text (de: 'Text', en: 'Text').
    expect($html)
        ->toContain('rounded-full')
        ->toContain('<svg') // Lucide-SVG-Icon
        ->toContain(__('block_type_text'))
        ->toContain('Inhalt');
});

it('Block-Card mit editing=true bekommt brand-bar-Rand und Suffix', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.block-card type="image" :editing="true">Body</x-ui.block-card>');

    // Editing-Zustand faerbt den Card-Rand um und haengt einen
    // 'wird bearbeitet'-Suffix ans Typ-Tag (Handoff v4 Screen 05a).
    expect($html)
        ->toContain('border-brand-bar')
        ->toContain(__('is_editing'));
});

it('Block-Card mit save-slot rendert Alpine-Store-Zugriff', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.block-card type="text" save-slot="Text-42">Body</x-ui.block-card>');

    expect($html)
        ->toContain("blocks['Text-42']")
        ->toContain('aria-live="polite"');
});

// ---------- Segmented Control (Phase 5-D.5) ----------

it('Segmented rendert role=tablist mit aria-selected je Item', function () {
    /** @var TestCase $this */
    $items = [
        ['label' => 'Bearbeiten', 'href' => '/edit', 'active' => true],
        ['label' => 'Übersetzen', 'href' => '/translate'],
        ['label' => 'Metadaten', 'href' => '/meta'],
    ];

    $html = Blade::render(
        '<x-ui.segmented :items="$items" aria-label="Modus"/>',
        ['items' => $items]
    );

    expect($html)
        ->toContain('role="tablist"')
        ->toContain('aria-label="Modus"')
        ->toContain('aria-selected="true"')
        ->toContain('aria-selected="false"')
        ->toContain('aria-current="page"');
});

// ---------- Media-Placeholder (Phase 5-D.6b P3.14) ----------

it('Media-Placeholder rendert Streifenmuster-Container mit Icon und Hint', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.media-placeholder type="image"/>');

    expect($html)
        ->toContain('cc-media-placeholder')
        ->toContain('aspect-ratio: 4/3')
        ->toContain('role="img"')
        ->toContain('aria-label');
});

it('Media-Placeholder respektiert aspect-Overrider', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.media-placeholder type="video" aspect="21/9"/>');

    expect($html)->toContain('aspect-ratio: 21/9');
});
