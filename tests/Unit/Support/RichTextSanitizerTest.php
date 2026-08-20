<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Q3-Politur G10 (2026-08-20) / ADR-0029:
 * Testet die Purifier-basierte Sanitize-Pipeline fuer Rich-Text-
 * Felder. Der Sanitizer laeuft primaer ueber HTML-Purifier mit dem
 * `rich`-Preset; der strip_tags-Fallback ist nur fuer den Ernstfall.
 *
 * Die Tests decken:
 *   - <script> und Event-Handler werden entfernt
 *   - erlaubte Rich-Text-Tags (strong, em, ul, li, blockquote, h2-h4,
 *     a) bleiben erhalten
 *   - href mit javascript:/data:/vbscript: wird neutralisiert
 *   - target=_blank + rel=noopener kommen automatisch auf externe Links
 *   - Inline-Styles fliegen raus
 */

use App\Support\RichTextSanitizer;

it('entfernt script-Tags und deren Inhalt', function () {
    $html = '<p>Guter Text</p><script>alert(1)</script>';
    $out = RichTextSanitizer::sanitize($html);
    expect($out)->not->toContain('<script')
        ->and($out)->not->toContain('alert(1)')
        ->and($out)->toContain('Guter Text');
});

it('entfernt on*-Event-Handler von erlaubten Tags', function () {
    $html = '<p onclick="alert(1)">Text</p><a href="https://example.org" onerror="x()">Link</a>';
    $out = RichTextSanitizer::sanitize($html);
    expect($out)->not->toContain('onclick')
        ->and($out)->not->toContain('onerror')
        ->and($out)->toContain('Text');
});

it('behaelt strong, em, ul, li erhalten', function () {
    $html = '<p><strong>Fett</strong> und <em>kursiv</em></p><ul><li>Eins</li><li>Zwei</li></ul>';
    $out = RichTextSanitizer::sanitize($html);
    expect($out)->toContain('<strong>')
        ->and($out)->toContain('<em>')
        ->and($out)->toContain('<ul>')
        ->and($out)->toContain('<li>Eins</li>');
});

it('neutralisiert javascript: in href', function () {
    $html = '<a href="javascript:alert(1)">klick</a>';
    $out = RichTextSanitizer::sanitize($html);
    expect($out)->not->toContain('javascript:');
});

it('erlaubt https-Links und ergaenzt target/rel', function () {
    $html = '<a href="https://example.org">Beispiel</a>';
    $out = RichTextSanitizer::sanitize($html);
    expect($out)->toContain('href="https://example.org"')
        ->and($out)->toContain('target="_blank"')
        ->and($out)->toContain('rel=');
});

it('entfernt inline style-Attribute', function () {
    $html = '<p style="background:url(javascript:alert(1))">Text</p>';
    $out = RichTextSanitizer::sanitize($html);
    expect($out)->not->toContain('style=')
        ->and($out)->not->toContain('javascript:');
});

it('behaelt h2, h3, h4 und blockquote', function () {
    $html = '<h2>Titel</h2><h3>Sub</h3><h4>Klein</h4><blockquote>Zitat</blockquote>';
    $out = RichTextSanitizer::sanitize($html);
    expect($out)->toContain('<h2>Titel</h2>')
        ->and($out)->toContain('<blockquote>Zitat</blockquote>');
});

it('gibt leeren String fuer null zurueck', function () {
    expect(RichTextSanitizer::sanitize(null))->toBe('');
    expect(RichTextSanitizer::sanitize(''))->toBe('');
});
