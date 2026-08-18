{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5ac.1 (Profil-Redesign Screen 17A): Erste Karte + Sofort-Wirkung
fuer Sprache und Theme + Sticky-Save-Fusszeile. Avatar-Upload,
Kuerzel-Sperrliste, Projekte-Karte, Passwort-Karte, Benachrichtigungen
und Konto-Loeschen folgen in 5ac.2–5ad.
--}}

@php
    $user = auth()->user();
    $palette = App\Support\ProfilePalette::TOKENS;
    $defaultInitials = mb_strtoupper(
        (mb_substr(trim((string) $user->name), 0, 1) ?: '?')
        .(mb_substr(trim((string) $user->last_name), 0, 1) ?: '')
    );
    $currentInitials = $user->initials ?? $defaultInitials;
    $currentColor = $user->initials_color ?? App\Support\ProfilePalette::defaultFor((string) $user->name, (string) $user->last_name);
    $currentLocale = $user->locale ?: app()->getLocale();
    $currentTheme = $user->theme ?: 'default';
    $languages = (array) config('languages');
@endphp

<x-layout>
    <x-slot:content>
        <div class="mx-auto max-w-4xl px-6 py-6">
            {{-- Kopf: KONTO · Mein Profil · Anmelde-Info rechts --}}
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                        {{ __('profile_kicker') }}
                    </p>
                    <h1 class="mt-1 text-title font-semibold text-ink-900">
                        {{ __('profile_page_title') }}
                    </h1>
                </div>
                <div class="text-caption text-ink-500">
                    {{ __('profile_signed_in_since', ['date' => optional($user->created_at)->format('d.m.Y')]) }}
                    ·
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('profile-logout').submit();"
                       class="underline hover:text-ink-900">
                        {{ __('profile_logout') }}
                    </a>
                    <form id="profile-logout" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
                </div>
            </div>

            {{-- Erfolgs- und Fehler-Meldungen aus dem Session-Flash --}}
            @if ($message = Session::get('success'))
                <div class="mb-4 rounded-md border border-success bg-success-bg px-4 py-2 text-body text-success">
                    {{ $message }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md border border-danger bg-danger-bg px-4 py-2 text-body text-danger">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}"
                  enctype="multipart/form-data"
                  x-data="ccProfileCard(@js([
                      'defaultInitials' => $defaultInitials,
                      'currentInitials' => $currentInitials,
                      'currentColor' => $currentColor,
                      'palette' => $palette,
                      'origFirst' => $user->name,
                      'origLast' => $user->last_name,
                      'origInitials' => $user->initials,
                      'origColor' => $user->initials_color,
                  ]))">
                @csrf
                @method('PATCH')

                {{-- Karte 1 · Person & Darstellung --}}
                <section class="rounded-lg border border-line-200 bg-paper-0 p-6 shadow-subtle">
                    <header class="mb-4">
                        <h2 class="text-heading font-semibold text-ink-900">{{ __('profile_card_person_title') }}</h2>
                        <p class="mt-1 text-body text-ink-500">{{ __('profile_card_person_desc') }}</p>
                    </header>

                    <div class="grid gap-6 md:grid-cols-[auto_1fr]">
                        {{-- Phase 5ac.2: Avatar-Upload. Original bleibt sichtbar,
                             Live-Preview per FileReader nach Auswahl. Entfernen
                             haengt an einem hidden-Feld, damit der Save-Endpoint
                             beide Faelle sauber trennt (Upload vs. Loeschen). --}}
                        <div class="flex flex-col items-start gap-2">
                            <div class="relative flex size-24 items-center justify-center overflow-hidden rounded-md text-heading font-semibold text-paper-0"
                                 :style="`background-color: var(--${currentColor})`">
                                @if ($user->avatar_path)
                                    <img x-show="!removedAvatar && !previewAvatarUrl"
                                         src="{{ Storage::disk('public')->url('uploads/avatars/'.$user->avatar_path) }}"
                                         alt="" class="absolute inset-0 size-full object-cover"/>
                                @endif
                                <img x-show="previewAvatarUrl" :src="previewAvatarUrl" alt="" class="absolute inset-0 size-full object-cover"/>
                                <span x-show="(removedAvatar || !previewAvatarUrl) && !hasStoredAvatar" x-text="displayInitials"></span>
                            </div>
                            <div class="flex gap-2">
                                <label class="cursor-pointer rounded-md border border-ink-300 bg-canvas-bg px-3 py-1 text-caption text-ink-900 hover:bg-chrome-active">
                                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                                           class="sr-only"
                                           @change="onAvatarPicked($event); dirty()"/>
                                    <span>{{ __('profile_avatar_replace') }}</span>
                                </label>
                                @if ($user->avatar_path)
                                    <button type="button" @click="removeAvatar(); dirty()"
                                            class="rounded-md border border-danger bg-paper-0 px-3 py-1 text-caption text-danger hover:bg-danger-bg">
                                        {{ __('profile_avatar_remove') }}
                                    </button>
                                @endif
                            </div>
                            <input type="hidden" name="remove_avatar" :value="removedAvatar ? '1' : '0'"/>
                            <p class="text-caption text-ink-500">{!! __('profile_avatar_hint_v2') !!}</p>
                        </div>

                        <div class="grid gap-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-caption font-medium text-ink-700" for="profile-first">
                                        {{ __('profile_first_name') }} <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <input id="profile-first" type="text" name="firstName"
                                           x-model="firstName" @input="dirty()"
                                           required
                                           class="w-full rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
                                </div>
                                <div>
                                    <label class="mb-1 block text-caption font-medium text-ink-700" for="profile-last">
                                        {{ __('profile_last_name') }} <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <input id="profile-last" type="text" name="lastName"
                                           x-model="lastName" @input="dirty()"
                                           required
                                           class="w-full rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
                                </div>
                            </div>

                            {{-- Kuerzel-Fallback: Kuerzel + Farbe.
                                 Sperrliste greift in 5ac.2. --}}
                            <div class="rounded-md border border-line-200 bg-canvas-bg p-3">
                                <div class="mb-2 flex items-center gap-3">
                                    <div class="flex size-9 items-center justify-center rounded text-caption font-semibold text-paper-0"
                                         :style="`background-color: var(--${currentColor})`"
                                         x-text="displayInitials"></div>
                                    <div class="min-w-0">
                                        <p class="text-body font-medium text-ink-900">{{ __('profile_initials_title') }}</p>
                                        <p class="text-caption text-ink-500">{{ __('profile_initials_desc') }}</p>
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-[auto_1fr]">
                                    <div>
                                        <label class="mb-1 block text-caption font-medium text-ink-700" for="profile-initials">
                                            {{ __('profile_initials_label') }}
                                        </label>
                                        <input id="profile-initials" name="initials" type="text"
                                               maxlength="3" size="4"
                                               x-model="initials" @input="normalizeInitials(); dirty()"
                                               @class([
                                                   'w-20 rounded-md border bg-paper-0 px-3 py-2 text-center font-mono uppercase text-ink-900',
                                                   'border-danger' => $errors->has('initials'),
                                                   'border-line-200' => ! $errors->has('initials'),
                                               ])/>
                                        @error('initials')
                                            <p class="mt-1 text-caption text-danger">{{ $message }}</p>
                                            @php
                                                $suggestions = App\Support\InitialsBlocklist::suggestFor(old('firstName', $user->name), old('lastName', $user->last_name));
                                            @endphp
                                            @if (! empty($suggestions))
                                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                                    <span class="text-caption text-ink-500">{{ __('profile_initials_suggestions') }}</span>
                                                    @foreach ($suggestions as $suggestion)
                                                        <button type="button" @click="initials = @js($suggestion); dirty()"
                                                                class="rounded-md border border-line-200 bg-canvas-bg px-2 py-0.5 font-mono text-caption text-ink-900 hover:bg-chrome-active">
                                                            {{ $suggestion }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-caption font-medium text-ink-700">
                                            {{ __('profile_initials_color') }}
                                        </label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($palette as $token)
                                                <button type="button" @click="pickColor(@js($token))"
                                                        :aria-pressed="currentColor === @js($token) ? 'true' : 'false'"
                                                        :class="currentColor === @js($token) ? 'ring-2 ring-ink-900 ring-offset-2 ring-offset-canvas-bg' : ''"
                                                        style="background-color: var(--{{ $token }})"
                                                        class="size-8 rounded-full border border-line-200"
                                                        aria-label="{{ $token }}"></button>
                                            @endforeach
                                        </div>
                                        <input type="hidden" name="initials_color" :value="currentColor"/>
                                    </div>
                                </div>
                            </div>

                            {{-- E-Mail (nur Anzeige) und Sprache/Theme (Sofort-Wirkung) --}}
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-caption font-medium text-ink-700">
                                        {{ __('profile_email') }}
                                    </label>
                                    <div class="flex items-center justify-between gap-3 rounded-md border border-dashed border-line-300 bg-canvas-bg px-3 py-2">
                                        <span class="min-w-0 truncate text-body text-ink-900">{{ $user->email }}</span>
                                        <span class="text-caption font-mono uppercase tracking-widest text-ink-500">{{ __('profile_view_only') }}</span>
                                    </div>
                                    <p class="mt-1 text-caption text-ink-500">
                                        {!! __('profile_email_change_hint') !!}
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-1 flex items-center gap-2 text-caption font-medium text-ink-700">
                                        <span>{{ __('profile_language') }}</span>
                                        <span class="rounded bg-canvas-dim px-1.5 py-0.5 text-caption font-mono uppercase tracking-widest text-ink-500">{{ __('profile_instant') }}</span>
                                    </p>
                                    <div class="flex gap-1 rounded-md bg-line-100 p-0.5" role="tablist" aria-label="{{ __('profile_language') }}">
                                        @foreach ($languages as $code => $label)
                                            <button type="button" role="tab"
                                                    @click="switchLocale(@js($code))"
                                                    :aria-selected="locale === @js($code) ? 'true' : 'false'"
                                                    :class="locale === @js($code) ? 'bg-paper-0 text-ink-900 shadow-subtle' : 'text-ink-600'"
                                                    class="flex-1 rounded-md px-3 py-1 text-caption font-medium">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>

                                    <p class="mt-3 mb-1 flex items-center gap-2 text-caption font-medium text-ink-700">
                                        <span>{{ __('profile_theme') }}</span>
                                        <span class="rounded bg-canvas-dim px-1.5 py-0.5 text-caption font-mono uppercase tracking-widest text-ink-500">{{ __('profile_instant') }}</span>
                                    </p>
                                    <div class="flex gap-1 rounded-md bg-line-100 p-0.5" role="tablist" aria-label="{{ __('profile_theme') }}">
                                        <button type="button" role="tab"
                                                @click="switchTheme('default')"
                                                :aria-selected="theme === 'default' ? 'true' : 'false'"
                                                :class="theme === 'default' ? 'bg-paper-0 text-ink-900 shadow-subtle' : 'text-ink-600'"
                                                class="flex-1 rounded-md px-3 py-1 text-caption font-medium">
                                            {{ __('profile_theme_default') }}
                                        </button>
                                        <button type="button" role="tab"
                                                @click="switchTheme('aktives-museum')"
                                                :aria-selected="theme === 'aktives-museum' ? 'true' : 'false'"
                                                :class="theme === 'aktives-museum' ? 'bg-paper-0 text-ink-900 shadow-subtle' : 'text-ink-600'"
                                                class="flex-1 rounded-md px-3 py-1 text-caption font-medium">
                                            {{ __('profile_theme_am') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Karte 2 · Meine Projekte & Rollen (Lese-Karte) --}}
                @if (isset($profileProjects) && $profileProjects->isNotEmpty())
                    <section class="mt-6 rounded-lg border border-line-200 bg-paper-0 p-6 shadow-subtle">
                        <header class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                            <div>
                                <h2 class="text-heading font-semibold text-ink-900">{{ __('profile_projects_title') }}</h2>
                                <p class="mt-1 text-body text-ink-500">{{ __('profile_projects_desc') }}</p>
                            </div>
                            <span class="rounded bg-canvas-dim px-1.5 py-0.5 text-caption font-mono uppercase tracking-widest text-ink-500">
                                {{ __('profile_view_only') }}
                            </span>
                        </header>

                        <ul class="divide-y divide-line-100">
                            @foreach ($profileProjects as $p)
                                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-body font-medium text-ink-900">{{ $p['name'] }}</p>
                                    </div>
                                    <span class="rounded-full bg-canvas-bg px-2 py-0.5 text-caption text-ink-700">{{ $p['role'] }}</span>
                                    <span class="text-caption text-ink-500">{{ $p['context'] }}</span>
                                    <a href="{{ route('projects.edit', $p['id']) }}"
                                       class="text-caption text-primary underline hover:no-underline">
                                        {{ __('profile_project_open') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- Sticky-Fusszeile mit Sammel-Speichern. Nennt beim Klick
                     was offen ist, damit der Nutzer nicht raetselt. --}}
                <div class="fixed inset-x-0 bottom-0 z-20 border-t border-line-200 bg-paper-0/95 shadow-medium backdrop-blur">
                    <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-6 py-3">
                        <p class="text-caption text-ink-500" x-text="pendingLabel"></p>
                        <div class="flex gap-2">
                            <button type="button" @click="reset()"
                                    :disabled="!isDirty"
                                    class="rounded-md border border-ink-300 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-chrome-active disabled:opacity-40">
                                {{ __('profile_discard') }}
                            </button>
                            <button type="submit"
                                    :disabled="!isDirty"
                                    class="rounded-md bg-primary px-4 py-1.5 text-caption font-semibold text-paper-0 hover:opacity-90 disabled:opacity-40">
                                {{ __('save') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="h-16"></div> {{-- Fusszeilen-Abstandhalter --}}

                <input type="hidden" x-init="locale = @js($currentLocale); theme = @js($currentTheme)"/>
            </form>
        </div>
    </x-slot:content>
</x-layout>

@push('scripts')
<script>
    // Phase 5ac.1: Alpine-Data fuer die Person-Karte plus Sofort-Wirkung
    // fuer Sprache und Theme (kein Save-Kandidat).
    window.ccProfileCard = function (init) {
        return {
            firstName: init.origFirst || '',
            lastName: init.origLast || '',
            initials: init.origInitials || '',
            currentColor: init.currentColor || init.palette[0],
            palette: init.palette,
            locale: '{{ $currentLocale }}',
            theme: '{{ $currentTheme }}',
            _dirty: false,
            // Phase 5ac.2: Avatar-Client-State.
            hasStoredAvatar: {{ $user->avatar_path ? 'true' : 'false' }},
            removedAvatar: false,
            previewAvatarUrl: null,
            get displayInitials() {
                if (this.initials && this.initials.trim() !== '') return this.initials.trim().toUpperCase();
                const a = (this.firstName || '').trim().charAt(0);
                const b = (this.lastName || '').trim().charAt(0);
                return (a + b).toUpperCase() || '?';
            },
            get isDirty() { return this._dirty; },
            get pendingLabel() {
                if (! this._dirty) return @js(__('profile_no_pending'));
                return @js(__('profile_pending_label'));
            },
            dirty() { this._dirty = true; },
            normalizeInitials() {
                this.initials = (this.initials || '').toUpperCase().replace(/[^A-ZÄÖÜ0-9]/g, '').slice(0, 3);
            },
            pickColor(t) { this.currentColor = t; this._dirty = true; },
            onAvatarPicked(event) {
                const file = event.target.files?.[0];
                if (!file) return;
                this.removedAvatar = false;
                const reader = new FileReader();
                reader.onload = (e) => { this.previewAvatarUrl = e.target?.result; };
                reader.readAsDataURL(file);
            },
            removeAvatar() {
                this.removedAvatar = true;
                this.previewAvatarUrl = null;
                this.hasStoredAvatar = false;
            },
            reset() {
                this.firstName = init.origFirst || '';
                this.lastName = init.origLast || '';
                this.initials = init.origInitials || '';
                this.currentColor = init.currentColor || init.palette[0];
                this._dirty = false;
            },
            async switchLocale(code) {
                if (this.locale === code) return;
                this.locale = code;
                await this._persist(@js(route('profile.locale')), { locale: code });
                window.location.reload();
            },
            async switchTheme(t) {
                if (this.theme === t) return;
                this.theme = t;
                document.documentElement.dataset.theme = t;
                await this._persist(@js(route('profile.theme')), { theme: t });
            },
            async _persist(url, body) {
                const token = document.querySelector('meta[name=csrf-token]')?.content;
                try {
                    await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(body),
                    });
                } catch (e) {
                    window.ccToast?.(@js(__('profile_save_failed')), 'error');
                }
            },
        };
    };
</script>
@endpush
