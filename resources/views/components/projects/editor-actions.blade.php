{{--
    crowdCuratio - Curating together virtually
    Copyright (C) 2026 - berlinHistory e.V.

    Q4-Etappe 7 · E7-4 (2026-09-11): Publish-Button + ⋮-Menu als
    wiederverwendbares Chrome-Actions-Bauteil. Sitzt in jedem
    Screen (Bearbeiten, Metadaten, Uebersetzen, Quellen,
    Berechtigungen), damit der Nutzer die zentralen Projekt-
    Aktionen ueberall in der Kopfzeile findet — nicht nur beim
    Bearbeiten. Extrahiert aus `chapters/index.blade.php`.

    Props:
    - `project`  — das Projekt (fuer Route-Bindings + Gates)
--}}

@props(['project'])

@if (Auth::user()?->can('publish', $project) || Auth::user()?->can('preview'))
    <button
        type="button"
        data-toggle="modal"
        data-target="#previewModal"
        class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2
               text-body font-medium text-primary-on hover:opacity-90
               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
    >
        {{ __('publish') }}
    </button>
@endif

{{-- Karl 2026-09-11: Das Export-Modal DARF hier NICHT eingehängt sein.
     Chrome ist sticky mit z-20 und erzeugt damit einen eigenen
     Stacking-Context — ein Modal als Descendant kann mit z:1050
     dann nicht mehr über andere Content-Boxen steigen (sichtbarer
     Effekt: nur die obere Kante des Modals ist zu sehen, der Rest
     verschwindet hinter den Editor-Boxen).

     Jede Chrome-Nutzende View bindet <x-projects.export-modal>
     stattdessen selbst ein, direkt neben (nicht in) <x-projects.chrome>. --}}
@can('update', $project)
    <div x-data="{ open: false }" class="relative">
        <button
            type="button"
            @click="open = !open"
            @click.outside="open = false"
            aria-haspopup="true"
            :aria-expanded="open"
            class="inline-flex size-9 items-center justify-center rounded-md text-ink-500
                   hover:bg-line-100 hover:text-ink-900
                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
            title="{{ __('more_actions') }}"
            :aria-label="'{{ __('more_actions') }}'"
        >
            <x-icon name="ellipsis-vertical" size="5"/>
        </button>
        <div
            x-show="open"
            x-transition
            x-cloak
            class="absolute right-0 z-40 mt-1 min-w-[14rem]
                   rounded-md border border-line-200 bg-paper-0 py-1 shadow-popover"
        >
            @if (Auth::user()?->can('publish', $project) || Auth::user()?->can('preview'))
                <button type="button"
                        data-toggle="modal"
                        data-target="#previewModal"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-900 hover:bg-line-100/60">
                    <x-icon name="file-text" size="4"/>
                    <span>{{ __('pdf') }}</span>
                </button>
                <button type="button"
                        data-toggle="modal"
                        data-target="#previewModal"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-900 hover:bg-line-100/60">
                    <x-icon name="globe" size="4"/>
                    <span>{{ __('preview') }}</span>
                </button>
                <a href="https://app.crowdcurat.io/downloads/html.zip"
                   target="_blank" rel="noopener"
                   class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-900 hover:bg-line-100/60">
                    <x-icon name="download" size="4"/>
                    <span>{{ __('download') }}</span>
                </a>
                <div class="my-1 border-t border-line-100"></div>
            @endif
            <form action="{{ route('projects.destroy', $project->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    onclick="return confirm('{{ __('message_delete_confirm') }}')"
                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-danger hover:bg-danger-bg"
                >
                    <x-icon name="trash-2" size="4"/>
                    <span>{{ __('delete_project') }}</span>
                </button>
            </form>
        </div>
    </div>
@endcan
