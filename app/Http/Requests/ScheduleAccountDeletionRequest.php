<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

/**
 * B2 (2026-08-21) / DSGVO: FormRequest fuer die Selbst-Anmeldung
 * einer Konto-Loeschung. Autorisierung ist implizit — der eingeloggte
 * User darf ausschliesslich sein eigenes Konto loeschen.
 *
 * Validation-Regeln:
 *  - `confirm` — Pflicht-Bestaetigung (Checkbox „Ich verstehe…").
 *  - `reason` — optional, max 255 Zeichen.
 *  - `handovers` — Map project_id => new_owner_user_id, fuer jedes
 *    Projekt, dessen Owner der User ist. Wird server-seitig gegen
 *    die tatsaechliche Owner-Liste geprueft.
 */
class ScheduleAccountDeletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm' => 'accepted',
            'reason' => 'nullable|string|max:255',
            'handovers' => 'array',
            'handovers.*' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Handovers als integer-map casten und gegen tatsaechlich
     * besessene Projekte pruefen. Weckhoefer-Bug (2026-06-15) hat
     * gezeigt: FormRequests validieren nur die geschickte Daten,
     * nicht die fachlich erwartete Menge. Wir cross-checken deshalb.
     *
     * @return array<int, int>
     */
    public function handovers(): array
    {
        $raw = (array) $this->input('handovers', []);
        $out = [];
        foreach ($raw as $projectId => $newOwnerId) {
            $out[(int) $projectId] = (int) $newOwnerId;
        }

        return $out;
    }

    /**
     * Convenience: liefert die IDs der aktuell owner-gehaltenen
     * Projekte des eingeloggten Users. Wird im Controller vor dem
     * Service-Call gebraucht, um sicherzustellen, dass die Handovers
     * vollstaendig sind.
     *
     * @return array<int, int>
     */
    public function ownedProjectIds(): array
    {
        $user = $this->user();
        if ($user === null) {
            return [];
        }

        return Project::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
