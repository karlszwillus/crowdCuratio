{{--
    crowdCuratio - Curating together virtually
    Copyright (C) 2026 - berlinHistory e.V.

    Q4-Etappe 7 · E7-4 (2026-09-11): Speicher-Zustand in der Metadaten-
    Kopfzeile. Liest den Alpine-Store `metadataAutosave` reaktiv aus
    und rendert vier Zustaende als reines Label:

      - „Alle Aenderungen gespeichert · HH:MM" (dirty=false, saving=false)
      - „Aenderungen offen"                    (dirty=true,  saving=false)
      - „Speichere …"                          (saving=true)
      - „Bild-Aenderung: bitte manuell speichern" (fileDirty=true)

    Karl 2026-09-11: Kein Speichern-Button, kein Verwerfen-Button — die
    Message ist der Anker. Autosave laeuft im Hintergrund, Verwerfen
    waere nach dem Debounce-Save eh wirkungslos (Reload holt den bereits
    gespeicherten Stand). Bei fileDirty fuehrt ein Klick irgendwo im
    Formular (Enter, nativer Submit) den klassischen Upload-Weg durch.
--}}

<div class="flex items-center" x-data aria-live="polite">
    <span class="text-caption text-ink-500"
          x-show="!$store.metadataAutosave.dirty && !$store.metadataAutosave.saving && !$store.metadataAutosave.fileDirty">
        <template x-if="$store.metadataAutosave.savedAtLabel">
            <span>
                {{ __('metadata_autosave_saved_prefix') }}<span x-text="$store.metadataAutosave.savedAtLabel"></span>
            </span>
        </template>
        <template x-if="!$store.metadataAutosave.savedAtLabel">
            <span>{{ __('metadata_autosave_idle') }}</span>
        </template>
    </span>
    <span class="text-caption text-warning"
          x-cloak
          x-show="$store.metadataAutosave.dirty && !$store.metadataAutosave.saving && !$store.metadataAutosave.fileDirty">
        {{ __('metadata_autosave_dirty') }}
    </span>
    <span class="text-caption text-ink-500"
          x-cloak
          x-show="$store.metadataAutosave.saving">
        {{ __('metadata_autosave_saving') }}
    </span>
    <span class="text-caption text-warning"
          x-cloak
          x-show="$store.metadataAutosave.fileDirty">
        {{ __('metadata_autosave_file_dirty') }}
    </span>
</div>
