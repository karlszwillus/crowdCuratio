<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

/**
 * Phase 5z.8: URL-Normalisierung für Video-Blöcke.
 *
 * Der Editor akzeptiert die Watch-URL wie sie im Browser steht
 * (`https://www.youtube.com/watch?v=xxx`, `https://youtu.be/xxx`,
 * `https://www.youtube.com/embed/xxx`). Das System bildet daraus
 * die Embed-URL für den Player und liefert die YouTube-ID einzeln
 * für Plyr.
 *
 * Nicht-YouTube-Quellen liefern null zurück — der Aufrufer entscheidet,
 * ob das ein Fehlerzustand ist oder ob der rohe Link roh eingebettet
 * wird (Fallback bis Vimeo/andere Provider hinzukommen).
 */
final class VideoLink
{
    /**
     * Extrahiert die YouTube-Video-ID aus einer beliebigen YouTube-
     * URL. Gibt null zurück, wenn kein YouTube erkennbar ist.
     */
    public static function extractYouTubeId(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // watch?v=..., youtu.be/..., embed/..., shorts/...
        $patterns = [
            '#youtube\.com/watch\?v=([A-Za-z0-9_-]{6,})#i',
            '#youtu\.be/([A-Za-z0-9_-]{6,})#i',
            '#youtube\.com/embed/([A-Za-z0-9_-]{6,})#i',
            '#youtube\.com/shorts/([A-Za-z0-9_-]{6,})#i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * Wandelt eine Watch/Short/Embed-URL in eine kanonische
     * Embed-URL. Gibt null zurück, wenn die URL nicht als YouTube
     * erkennbar ist — der Aufrufer kann dann den Fehlerzustand
     * rendern.
     */
    public static function toEmbedUrl(string $url): ?string
    {
        $id = self::extractYouTubeId($url);
        if ($id === null) {
            return null;
        }

        return 'https://www.youtube.com/embed/'.$id;
    }

    /**
     * Poster-URL für YouTube. `hqdefault` ist immer verfügbar und
     * schont die Bandbreite gegenüber `maxresdefault`.
     */
    public static function youTubePoster(string $id): string
    {
        return 'https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg';
    }
}
