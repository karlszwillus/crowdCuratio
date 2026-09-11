{{--
    crowdCuratio - Curating together virtually
    Copyright (C) 2026 - berlinHistory e.V.

    Q4-Etappe 7 · E7-4 (2026-09-11): Speicher-Status fuer die
    Nicht-Metadaten-Screens (Bearbeiten, Uebersetzen, Quellen,
    Berechtigungen). Liest den globalen `saveStatus`-Store aus, den
    die Inline-Editoren per Livewire-Event fuellen — und gibt ihn im
    selben Format wie Metadaten aus:

      idle + Zeitstempel  → „Alle Änderungen gespeichert · HH:MM"
      idle ohne Zeitstempel → „Noch keine Änderungen"
      saving              → „Speichere …"
      saved               → wie idle+Zeitstempel, kurzer Farb-Puls
      error               → „Nicht gespeichert"

    Karl 2026-09-11: Konsistentes Vokabular ueber alle Chrome-Screens.
--}}

<div class="flex items-center" x-data aria-live="polite">
    {{-- Idle: „Alle Änderungen gespeichert · HH:MM" wenn es einen
         letzten Save gab, sonst „Noch keine Änderungen". --}}
    <span class="text-caption text-ink-500"
          x-show="$store.saveStatus.state === 'idle' && $store.saveStatus.lastSavedAt">
        {{ __('metadata_autosave_saved_prefix') }}<span
            x-text="new Date($store.saveStatus.lastSavedAt).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})"></span>
    </span>
    <span class="text-caption text-ink-500"
          x-show="$store.saveStatus.state === 'idle' && !$store.saveStatus.lastSavedAt">
        {{ __('metadata_autosave_idle') }}
    </span>

    {{-- Saving: Debounce-Roundtrip laeuft. --}}
    <span class="text-caption text-ink-500"
          x-cloak
          x-show="$store.saveStatus.state === 'saving'">
        {{ __('metadata_autosave_saving') }}
    </span>

    {{-- Saved: kurzer Erfolgs-Puls, dann faellt der Store auf idle
         zurueck (Auto-Fade in save-status.js). --}}
    <span class="text-caption text-success"
          x-cloak
          x-show="$store.saveStatus.state === 'saved'"
          x-transition:enter="transition-opacity duration-150 ease-out"
          x-transition:enter-start="opacity-0"
          x-transition:enter-end="opacity-100">
        {{ __('metadata_autosave_saved_prefix') }}<span
            x-text="new Date($store.saveStatus.lastSavedAt).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})"></span>
    </span>

    {{-- Fehler: Save fehlgeschlagen. --}}
    <span class="text-caption text-danger"
          x-cloak
          x-show="$store.saveStatus.state === 'error'">
        {{ __('save_status_error') }}
    </span>
</div>
