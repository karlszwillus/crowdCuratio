<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Services;

use App\Traits\UploadTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 5ac.2 (Profil § 2): Avatar-Upload analog zum Projekt-Bild.
 *
 * JPG/PNG/WebP, max. 2 MB — Validierung im FormRequest, der Service
 * legt die Datei ab und liefert den Dateinamen. Cleanup der
 * Vorgaengerdatei laeuft parallel, damit verwaiste Datei-Blobs nicht
 * im Storage bleiben.
 */
class AvatarService
{
    use UploadTrait;

    public function store(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }
        $filename = 'avatar-'.date('Ymd').'-'.uniqid().'.'.$file->extension();
        $folder = '/uploads/avatars/';
        $this->uploadOne($file, $folder, 'public', $filename);

        return $filename;
    }

    /**
     * Loescht eine vorhandene Avatar-Datei — no-op bei null/leer.
     */
    public function remove(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }
        $path = 'uploads/avatars/'.$filename;
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
