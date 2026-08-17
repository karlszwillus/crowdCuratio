<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

use App\Models\Imprint;
use App\Models\Project;
use App\Models\TermsConditions;

/**
 * Phase 5aa.2/Design v6 § 3: Auflösung des Legal-Textes für ein Projekt.
 *
 * Ein Projekt darf Impressum und AGB überschreiben; bleibt das Feld leer,
 * greift der systemweite Text aus /settings automatisch. Beide Aufrufer
 * (Metadata-Sicht, HTML-/PDF-Export) laufen jetzt über diesen Helper,
 * damit die Regel an einer Stelle steht.
 *
 * `imprintFor` und `termsFor` liefern immer den effektiv verwendeten Wert.
 * `systemImprint` und `systemTerms` sind Convenience-Zugriffe für die
 * Metadaten-Sicht, damit sie den Systemtext für das „Systemtext übernehmen"-
 * Preview zeigen kann.
 */
final class ProjectLegalText
{
    public static function imprintFor(Project $project): string
    {
        $projectValue = trim((string) $project->imprint);
        if ($projectValue !== '') {
            return $projectValue;
        }

        return self::systemImprint();
    }

    public static function termsFor(Project $project): string
    {
        $projectValue = trim((string) $project->terms);
        if ($projectValue !== '') {
            return $projectValue;
        }

        return self::systemTerms();
    }

    public static function systemImprint(): string
    {
        /** @var Imprint|null $imprint */
        $imprint = Imprint::first();
        if ($imprint === null) {
            return '';
        }

        // Der Imprint ist strukturiert (name/address/contact). Wir formatieren
        // eine schmale HTML-Ausgabe die zur „Angaben gem: …"-Anzeige der Legacy-
        // Cards passt und in den Publish-Templates lesbar bleibt.
        $lines = [];
        $firstName = $imprint->name['firstname'] ?? '';
        $lastName = $imprint->name['lastname'] ?? '';
        $fullName = trim($firstName.' '.$lastName);
        if ($fullName !== '') {
            $lines[] = e($fullName);
        }
        if (! empty($imprint->address['address'] ?? '')) {
            $lines[] = e($imprint->address['address']);
        }
        if (! empty($imprint->address['postcode'] ?? '')) {
            $lines[] = e($imprint->address['postcode']);
        }
        if (! empty($imprint->contact['phone'] ?? '')) {
            $lines[] = e($imprint->contact['phone']);
        }
        if (! empty($imprint->contact['email'] ?? '')) {
            $lines[] = e($imprint->contact['email']);
        }

        return implode('<br>', $lines);
    }

    public static function systemTerms(): string
    {
        /** @var TermsConditions|null $terms */
        $terms = TermsConditions::first();

        return (string) ($terms->terms_conditions ?? '');
    }
}
