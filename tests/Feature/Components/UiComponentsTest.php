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

// ---------- Icon ----------

it('Icon rendert SVG mit aria-hidden und focusable=false', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.icon name="check"/>');

    expect($html)
        ->toContain('<svg')
        ->toContain('aria-hidden="true"')
        ->toContain('focusable="false"');
});

it('Icon ohne Match in Library rendert leeres SVG', function () {
    /** @var TestCase $this */
    $html = Blade::render('<x-ui.icon name="does-not-exist"/>');

    expect($html)->toContain('<svg');
    expect(str_contains($html, '<path'))->toBeFalse();
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
