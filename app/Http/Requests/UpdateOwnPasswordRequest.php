<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

/**
 * Phase 5ac.4 (Profil § 4): eigener Endpoint fuer den Passwort-Wechsel,
 * damit die Karte einen eigenen Save-Button hat und der Profil-Save
 * die anderen Felder pflegt. Regeln nach Design v6: mind. 10 Zeichen,
 * keine Ziffern-Pflicht, altes Passwort muss stimmen.
 */
class UpdateOwnPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'old_password' => [
                'required', 'string',
                function ($attribute, $value, $fail): void {
                    if (! Hash::check((string) $value, (string) $this->user()->password)) {
                        $fail(__('message_old_password_incorrect'));
                    }
                },
            ],
            'new_password' => 'required|string|min:10|different:old_password',
            'confirm_password' => 'required|string|same:new_password',
        ];
    }
}
