{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5ac.1 (Profil-Redesign Screen 17A): Erste Karte + Sofort-Wirkung
fuer Sprache und Theme + Sticky-Save-Fusszeile. Avatar-Upload,
Kuerzel-Sperrliste, Projekte-Karte, Passwort-Karte, Benachrichtigungen
und Konto-Loeschen folgen in 5ac.2–5ad.
--}}

{{-- Phase 5ac.1 Fix: die Alpine-Data muss VOR dem alpine:init-Event
     verfügbar sein, sonst wertet Alpine `x-data="ccProfileCard(...)"`
     mit undefined aus und Vorname/Nachname bleiben leer, Toggles tot.
     `@push('scripts')` rendert erst nach @livewireScripts — dort ist
     Alpine schon zu spaet. Deshalb inline direkt VOR der Sicht. --}}
@php
    // Blade-Directive-Parser stolpert ueber @json(...) mit einem
    // Array-Literal, das mehrere Funktionsaufrufe enthaelt. Deshalb
    // vorher als Variable zuweisen und dann @json($var) rendern.
    // Q3-Politur G1 (2026-08-20) / LIVE-UX-01: strength=0 hat kein Label
    // (Nutzer:in hat nichts getippt). Frueher: __('profile_pw_strength_0')
    // mit leerem JSON-Wert. Laravel gibt bei leerem oder fehlendem Key
    // den Key selbst zurueck; das rutschte als woertliche Ausgabe ins UI.
    $pwStrengthLabels = [
        '',
        __('profile_pw_strength_1'),
        __('profile_pw_strength_2'),
        __('profile_pw_strength_3'),
        __('profile_pw_strength_4'),
    ];
@endphp
<script>
    window.ccPasswordCard = function () {
        return {
            pw: '', confirm: '',
            // Q3-Politur G1 (2026-08-20) / UX-02: Submit-Feedback.
            submitting: false,
            get strength() {
                const s = this.pw || '';
                if (s.length === 0) return 0;
                let score = 0;
                if (s.length >= 10) score++;
                if (s.length >= 14) score++;
                if (/[A-Z]/.test(s) && /[a-z]/.test(s)) score++;
                if (/[^A-Za-z0-9]/.test(s)) score++;
                return Math.min(score, 4);
            },
            get strengthLabel() {
                const labels = @json($pwStrengthLabels);
                return labels[this.strength] || '';
            }
        };
    };
    window.ccProfileCard = function (init) {
        return {
            firstName: init.origFirst || '',
            lastName: init.origLast || '',
            initials: init.origInitials || '',
            currentColor: init.currentColor || init.palette[0],
            palette: init.palette,
            locale: init.locale,
            theme: init.theme,
            _dirty: false,
            // Q3-Politur G1 (2026-08-20) / UX-02: Submit-Feedback.
            submitting: false,
            hasStoredAvatar: !! init.hasStoredAvatar,
            removedAvatar: false,
            previewAvatarUrl: null,
            get showAvatarImage() {
                if (this.previewAvatarUrl) return true;
                if (this.removedAvatar) return false;
                return this.hasStoredAvatar;
            },
            get displayInitials() {
                if (this.initials && this.initials.trim() !== '') return this.initials.trim().toUpperCase();
                const a = (this.firstName || '').trim().charAt(0);
                const b = (this.lastName || '').trim().charAt(0);
                return (a + b).toUpperCase() || '?';
            },
            get isDirty() { return this._dirty; },
            get pendingLabel() {
                return this._dirty ? init.pendingLabel : init.noPendingLabel;
            },
            dirty() { this._dirty = true; },
            normalizeInitials() {
                this.initials = (this.initials || '').toUpperCase().replace(/[^A-ZÄÖÜ0-9]/g, '').slice(0, 3);
            },
            // Q3-Politur G9 (2026-08-20) / UX-01: Live-Blur-Check gegen
            // die Sperrliste. Ergaenzt den Server-Validator, ersetzt ihn
            // nicht — beim Save laeuft nochmal derselbe Check.
            liveInitialsBlocked: false,
            liveInitialsMessage: '',
            liveInitialsSuggestions: [],
            async checkInitialsRemote() {
                const value = (this.initials || '').trim();
                if (value === '') {
                    this.liveInitialsBlocked = false;
                    this.liveInitialsMessage = '';
                    this.liveInitialsSuggestions = [];
                    return;
                }
                const token = document.querySelector('meta[name=csrf-token]')?.content;
                try {
                    const res = await fetch(init.checkInitialsUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ initials: value }),
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.liveInitialsBlocked = !!data.blocked;
                    this.liveInitialsMessage = data.message || '';
                    this.liveInitialsSuggestions = Array.isArray(data.suggestions) ? data.suggestions : [];
                } catch (e) {
                    // Netzwerkfehler: stumm. Server-Save fangt es auf.
                }
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
                // Q3-Politur G9 (2026-08-20) / UX-10: der Reload nach
                // Locale-Wechsel wuerde ungespeicherte Aenderungen im
                // Formular verwerfen. Vorher fragen, wenn was offen ist.
                if (this._dirty || (this.$root && this.$root.submitting)) {
                    const msg = init.localeConfirm || 'Ungespeicherte Änderungen gehen verloren. Sprache trotzdem wechseln?';
                    if (! window.confirm(msg)) return;
                }
                this.locale = code;
                await this._persist(init.localeUrl, { locale: code });
                window.location.reload();
            },
            async switchTheme(t) {
                if (this.theme === t) return;
                this.theme = t;
                // Der globale Alpine-Store aus resources/js/theme.js
                // hat die Fallunterscheidung (data-theme-Attribut + LS
                // persistieren) — Doppelarbeit hier vermeiden.
                try { window.Alpine.store('theme').set(t); } catch (e) {
                    document.documentElement.setAttribute('data-theme', t === 'crowdCuratio' ? '' : t);
                }
                await this._persist(init.themeUrl, { theme: t });
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
                    window.ccToast?.(init.saveFailedLabel, 'error');
                }
            },
        };
    };
</script>

@php
    $user = auth()->user();
    $palette = App\Support\ProfilePalette::TOKENS;
    // shouldBeStrict() sperrt Direkt-Zugriff auf Attribute, die weder im
    // attributes-Array noch als Cast/Mutator existieren. Bei einem User,
    // der via Factory frisch angelegt und nicht neu geladen wurde,
    // fehlen die 5ac.1-Felder (locale/theme/initials/…) — der Direkt-
    // Zugriff crasht. Defensiv-Getter mit try/catch fuellt sie aus DB
    // via getAttribute() oder fällt auf den Default zurück.
    $safeGet = static function ($model, string $key, $default = null) {
        try {
            $value = $model?->getAttribute($key);
            return $value ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    };
    $userName = (string) $safeGet($user, 'name', '');
    $userLastName = (string) $safeGet($user, 'last_name', '');
    $defaultInitials = mb_strtoupper(
        (mb_substr(trim($userName), 0, 1) ?: '?')
        .(mb_substr(trim($userLastName), 0, 1) ?: '')
    );
    $currentInitials = $safeGet($user, 'initials') ?: $defaultInitials;
    $currentColor = $safeGet($user, 'initials_color') ?: App\Support\ProfilePalette::defaultFor($userName, $userLastName);
    $currentLocale = $safeGet($user, 'locale') ?: app()->getLocale();
    // Theme-Namen matchen die Werte aus resources/js/theme.js
    // (`crowdCuratio` / `aktivesMuseum`). Alte 'default'-Werte in der
    // DB fallen auf die Standard-Wahl zurueck.
    $userTheme = $safeGet($user, 'theme');
    $currentTheme = in_array($userTheme, ['crowdCuratio', 'aktivesMuseum'], true)
        ? $userTheme
        : 'crowdCuratio';
    $userAvatarPath = $safeGet($user, 'avatar_path');
    $languages = (array) config('languages');
@endphp

<x-layout>
    <x-slot:content>
        {{-- Kein mx-auto: der x-layout content-Slot rechnet nicht mit
             sidebar, die Rail links versetzt sonst die optische Mitte. --}}
        <div class="max-w-4xl px-6 py-6">
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

            {{-- Q4-Etappe 8 · I9 (2026-09-12): profile.blade.php auf drei
                 Sub-Views verteilt (Person / Passwort / Konto-Loeschung)
                 — Datei ist damit unter 300 LoC statt vorher 760. --}}
            @include("users.profile._person")
            @include("users.profile._password")
            @include("users.profile._deletion")

            <div class="h-16"></div>{{-- Fusszeilen-Abstandhalter --}}
        </div>
    </x-slot:content>
</x-layout>

