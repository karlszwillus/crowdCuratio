<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Support;

/**
 * Q4-Etappe 1 / I4 (2026-08-27): Preset-Masken fuer die Rollen-Vorlagen
 * in der project-permissions-Volt-Komponente.
 *
 * Vorher lebten die drei Masken (Editor / Reviewer / Reader) 1:1 sowohl
 * in `applyPreset()` als auch in `getMatchedPresetProperty()` (~50 LoC
 * Duplikat). Der Dedup zentralisiert sie hier und verhindert Drift —
 * wer einen neuen Wert einfuegt, muss nur eine Stelle anfassen.
 *
 * Fuer den Vergleich in `getMatchedPresetProperty()` ist die
 * Iterationsreihenfolge stabil (PHP-Arrays sind ordered), damit
 * `$mask === $current` funktioniert.
 */
final class PermissionPresets
{
    /**
     * @return array<string, array<string, bool>>
     */
    public static function masks(): array
    {
        return [
            RoleName::EDITOR->value => [
                PermissionName::EDIT->value => true,
                PermissionName::ADD->value => true,
                PermissionName::DELETE->value => true,
                PermissionName::PUBLISH->value => true,
                PermissionName::COMMENT->value => true,
                PermissionName::INVITE->value => false,
            ],
            RoleName::REVIEWER->value => [
                PermissionName::EDIT->value => false,
                PermissionName::ADD->value => false,
                PermissionName::DELETE->value => false,
                PermissionName::PUBLISH->value => false,
                PermissionName::COMMENT->value => true,
                PermissionName::INVITE->value => false,
            ],
            RoleName::READER->value => [
                PermissionName::EDIT->value => false,
                PermissionName::ADD->value => false,
                PermissionName::DELETE->value => false,
                PermissionName::PUBLISH->value => false,
                PermissionName::COMMENT->value => false,
                PermissionName::INVITE->value => false,
            ],
        ];
    }
}
