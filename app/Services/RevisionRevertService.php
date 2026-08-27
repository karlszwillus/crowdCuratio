<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Text;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * B2-Nachlauf / Q3-Abschluss (2026-08-27) — Verlauf-Wiederherstellen.
 *
 * Vorher: `ProjectController::resetValue` (~78 LoC) machte die Whitelist,
 * die Feld-Zuweisung und den Save inline. Der Refactor extrahiert die
 * Feld-Zuweisung in diesen Service, damit der Controller nur noch
 * Whitelist-Check + Authorize + Delegate leistet.
 *
 * Der Service kennt die zu einem Content-Modell erlaubten Reset-Felder
 * ueber eine Konstante und wendet nur die im Request tatsaechlich
 * uebergebenen an. Die Semantik entspricht 1:1 dem Legacy-Endpunkt —
 * einschliesslich des dokumentierten Legacy-Bugs bei `originReset`.
 */
final class RevisionRevertService
{
    /**
     * Whitelist der Content-Modelle, deren Werte per resetValue-Endpunkt
     * zurueckgesetzt werden duerfen. Security-Sweep-III (2026-06-22)
     * hat die Whitelist eingefuehrt, weil der alte Endpunkt jede
     * beliebige Klasse mit findOrFail() instantiieren konnte.
     *
     * @var list<class-string<Model>>
     */
    public const REVERTIBLE_MODELS = [
        Chapter::class,
        Entry::class,
        Text::class,
        Image::class,
        Gallery::class,
        Audiovisual::class,
    ];

    public function __construct(
        private readonly SourceService $sources,
    ) {}

    /**
     * True, wenn `$fqcn` in der Reset-Whitelist steht.
     */
    public function isRevertible(string $fqcn): bool
    {
        return in_array($fqcn, self::REVERTIBLE_MODELS, true);
    }

    /**
     * Wendet die im Request-Payload uebergebenen Reset-Werte auf das
     * uebergebene Modell an und speichert. Es werden nur die Felder
     * angefasst, fuer die tatsaechlich ein Reset-Key kam.
     *
     * @param  array<string, mixed>  $payload  Reset-Payload (z. B. `['nameReset' => 'alt', 'textReset' => '...']`)
     */
    public function revert(Model $model, array $payload): void
    {
        if (! $this->isRevertible($model::class)) {
            throw new InvalidArgumentException(
                'Model '.$model::class.' ist nicht revertible. Whitelist prüfen.',
            );
        }

        // Property-Zuweisungen laufen bewusst ueber setAttribute() —
        // die Whitelist-Modelle definieren die Felder unterschiedlich
        // (Chapter kennt kein `text`, Text kein `image`), und PHPStan
        // koennte gegen die abstrakte Model-Signatur sonst nichts
        // Sinnvolles pruefen. setAttribute geht durch die HasTranslations-
        // /Cast-Kette wie ein normaler Property-Setter.
        if (isset($payload['nameReset'])) {
            $model->setAttribute('name', $payload['nameReset']);
        }

        if (isset($payload['subtitleReset'])) {
            $model->setAttribute('subtitle', $payload['subtitleReset']);
        }

        if (isset($payload['descriptionReset'])) {
            $model->setAttribute('description', $payload['descriptionReset']);
        }

        if (isset($payload['copyrightReset'])) {
            $model->setAttribute(
                'copyright',
                $this->sources->findOrCreateId($payload['copyrightReset'], 'Copyright'),
            );
        }

        if (isset($payload['originReset'])) {
            // Legacy-Bug (aus resetValue uebernommen, 2026-08-27):
            // Origin-Reset landete im Legacy-Code auf `$model->copyright`
            // statt `origin`, und las den Wert aus `copyrightReset`
            // statt `originReset`. Reine Extraktion belaesst die
            // Semantik 1:1 — Fix folgt separat, weil er potenziell
            // Verlauf-Daten interpretiert.
            $model->setAttribute(
                'copyright',
                $this->sources->findOrCreateId(
                    $payload['copyrightReset'] ?? $payload['originReset'],
                    'Origin',
                ),
            );
        }

        if (isset($payload['textReset'])) {
            $model->setAttribute('text', $payload['noHighlight'] ?? $payload['textReset']);
        }

        if (isset($payload['imageReset'])) {
            $model->setAttribute('image', $payload['imageReset']);
        }

        if (isset($payload['urlReset'])) {
            $model->setAttribute('url', $payload['urlReset']);
        }

        if (isset($payload['sourceReset'])) {
            $model->setAttribute('source', $payload['sourceReset']);
        }

        if (isset($payload['linkReset'])) {
            $model->setAttribute('link', $payload['linkReset']);
        }

        $model->save();
    }
}
