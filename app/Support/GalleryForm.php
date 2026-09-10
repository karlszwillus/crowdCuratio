<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

/**
 * Q4-Etappe 6 · G6-2 (2026-09-10): Darstellungsform einer Galerie.
 *
 * Die Form ergibt sich aus der Bildanzahl, nicht aus einer
 * Redakteurs-Wahl (Design-Briefing Arbeitspaket 5, Empfehlung):
 *
 *   BAND         · 1–4 Bilder   · gleich hoch, alle sichtbar
 *   KONTAKTBOGEN · ab 5 Bildern · 3-Spalten-Grid mit „+n"-Overlay
 *
 * Ausnahme: die Sequenz-Bühne ist eine redaktionelle Ansage —
 * gesetzt oder nicht. Sie überschreibt die Anzahl-Regel, weil
 * Reihenfolge in Serien/Vorher-Nachher/mehrseitigen Dokumenten
 * selbst die Aussage ist.
 *
 * Reader-Rendering, Editor-Statusanzeige und PDF-Renderer greifen
 * auf dieselbe `resolve()`-Methode zurück, damit die drei Ebenen
 * niemals divergieren.
 */
enum GalleryForm: string
{
    case BAND = 'band';
    case KONTAKTBOGEN = 'kontaktbogen';
    case SEQUENZ = 'sequenz';

    /**
     * Anzahl-Regel: Sequenz überschreibt, sonst gilt die Schwelle
     * bei fünf Bildern. Weniger als ein Bild bleibt formal Band —
     * das Rendering entscheidet dann selbst, dass es „nichts zu
     * zeigen" gibt.
     */
    public static function resolve(int $imageCount, bool $sequence = false): self
    {
        if ($sequence) {
            return self::SEQUENZ;
        }

        return $imageCount >= 5 ? self::KONTAKTBOGEN : self::BAND;
    }

    /**
     * Deutsche Beschriftung für die Editor-Status-Pille
     * („Erscheint als …"). Cast auf `string`, weil Laravels
     * `__()` als `string|array|null` deklariert ist — für den
     * Aufrufer soll die Signatur reines `string` bleiben.
     */
    public function label(): string
    {
        return (string) match ($this) {
            self::BAND => __('gallery_form_band'),
            self::KONTAKTBOGEN => __('gallery_form_kontaktbogen'),
            self::SEQUENZ => __('gallery_form_sequenz'),
        };
    }

    /**
     * Kurzer Kontext-Text für die Editor-Kopfzeile („1–4 Bilder"
     * bzw. „ab 5 Bildern" bzw. „nur auf Ansage"), damit die
     * Regel im Editor jederzeit ablesbar bleibt.
     */
    public function scopeHint(): string
    {
        return (string) match ($this) {
            self::BAND => __('gallery_form_band_scope'),
            self::KONTAKTBOGEN => __('gallery_form_kontaktbogen_scope'),
            self::SEQUENZ => __('gallery_form_sequenz_scope'),
        };
    }

    /**
     * Zeigt an, ob ein weiteres Bild hoch- oder ein bestehendes
     * heruntergezählt zu einem Formwechsel führen würde. Der
     * Editor-Kopf zeigt darauf basierend einen Hinweis („Beim
     * fünften Bild wechselt der Block auf Kontaktbogen —
     * Nachweise wandern in die Lightbox").
     *
     * @return self|null Nächste Form, wenn Anzahl um 1 steigt.
     */
    public function nextFormIfIncrement(int $imageCount, bool $sequence): ?self
    {
        $next = self::resolve($imageCount + 1, $sequence);

        return $next === $this ? null : $next;
    }

    /**
     * Analog für den Fall „ein Bild wird gelöscht".
     */
    public function nextFormIfDecrement(int $imageCount, bool $sequence): ?self
    {
        if ($imageCount <= 0) {
            return null;
        }
        $next = self::resolve($imageCount - 1, $sequence);

        return $next === $this ? null : $next;
    }
}
