<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

/**
 * Phase 5ab.1 (Design v6 § 6): Kategorien einer Fassung.
 *
 * Der Chip auf der Fassungs-Karte nennt die Art der Aenderung; die
 * vier Kategorien decken das ab, was ein Kurator im Verlauf zuerst
 * ueberfliegen will. Der Observer entscheidet aus der Delta-Menge,
 * welche Kategorie zutrifft — Uebersetzungs-Setter → TRANSLATION,
 * `position`-Aenderung → REORDER, Text-Felder → CONTENT, alles
 * andere (Herkunft, Datei, Angaben) → FACTS.
 */
enum RevisionKind: string
{
    case CONTENT = 'content';
    case FACTS = 'facts';
    case REORDER = 'reorder';
    case TRANSLATION = 'translation';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Deutsche Chip-Beschriftung nach § 6 des Briefings.
     */
    public function label(): string
    {
        return match ($this) {
            self::CONTENT => __('revision_kind_content'),
            self::FACTS => __('revision_kind_facts'),
            self::REORDER => __('revision_kind_reorder'),
            self::TRANSLATION => __('revision_kind_translation'),
        };
    }
}
