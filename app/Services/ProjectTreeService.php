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

namespace App\Services;

use App\Models\Project;

/**
 * Zentrale Aufbereitung der Projekt-Hierarchie (Projekt → Kapitel →
 * Abschnitt) für die drei Sidebar-nahen Konsumenten: die
 * `<livewire:sidebar-tree>`-Volt-Komponente, das
 * `<x-ui.breadcrumb :tree>`-Alpine-Widget im Editor sowie potenziell
 * weitere Tree-Views (Reader, Print, PDF-Vorschau).
 *
 * Vor der Konsolidierung bauten Sidebar und Breadcrumb den Tree je
 * eigenständig auf — unterschiedliche Eager-Load-Pfade, doppelte
 * Traversierung, Drift-Risiko bei Struktur-Änderungen. Dieser Service
 * ist die Single Source of Truth: laden, mappen, ausliefern.
 */
class ProjectTreeService
{
    /**
     * Für die Alpine-Hash-Watcher-Breadcrumb: verschachteltes Array
     * mit dem `root`-Eintrag (Projekt) plus `chapters`-Map je Chapter-
     * ID; jedes Chapter enthält seine `entries`-Map je Entry-ID.
     *
     * Struktur ist stabil und wird direkt als JSON in das
     * `<x-ui.breadcrumb :tree="...">`-Attribut serialisiert.
     *
     * @return array{
     *     root: array{label: string, href: string},
     *     chapters: array<int, array{
     *         label: string,
     *         href: string,
     *         entries: array<int, array{label: string, href: string}>,
     *     }>,
     * }
     */
    public function breadcrumbTree(Project $project): array
    {
        $project = $project->loadMissing(['chapters.entries']);

        return [
            'root' => [
                'label' => (string) $project->name,
                'href' => '#main-content',
            ],
            'chapters' => $project->chapters
                ->mapWithKeys(fn ($chapter) => [
                    $chapter->id => [
                        'label' => (string) $chapter->name,
                        'href' => '#anchor_Chapter_'.$chapter->id,
                        'entries' => $chapter->entries
                            ->mapWithKeys(fn ($entry) => [
                                $entry->id => [
                                    'label' => (string) $entry->name,
                                    'href' => '#anchor_Entry_'.$entry->id,
                                ],
                            ])
                            ->toArray(),
                    ],
                ])
                ->toArray(),
        ];
    }

    /**
     * Für die Sidebar-Tree-Komponente: lädt den Project-Tree eager
     * für die zwei sichtbaren Ebenen (Kapitel und Abschnitte). Gibt
     * das Project-Modell selbst zurück — die Volt-Komponente
     * iteriert direkt über `$project->chapters` und
     * `$chapter->entries`.
     */
    public function sidebarTree(Project $project): Project
    {
        return $project->loadMissing(['chapters.entries']);
    }
}
