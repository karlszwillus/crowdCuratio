{{--
    crowdCuratio - Curating together virtually
    Copyright (C) 2026 - berlinHistory e.V.

    Q4-Etappe 8 · I9 (2026-09-12): Sub-View aus profile.blade.php
    extrahiert. Variablen kommen ueber @include aus dem Wrapper.
--}}
            <form method="POST" action="{{ route('profile.update') }}"
                  enctype="multipart/form-data"
                  x-data="ccProfileCard(@js([
                      'defaultInitials' => $defaultInitials,
                      'currentInitials' => $currentInitials,
                      'currentColor' => $currentColor,
                      'palette' => $palette,
                      'origFirst' => $userName,
                      'origLast' => $userLastName,
                      'origInitials' => $safeGet($user, 'initials'),
                      'origColor' => $safeGet($user, 'initials_color'),
                      'locale' => $currentLocale,
                      'theme' => $currentTheme,
                      'hasStoredAvatar' => (bool) $userAvatarPath,
                      'localeUrl' => route('profile.locale'),
                      'themeUrl' => route('profile.theme'),
                      'localeConfirm' => __('profile_locale_switch_confirm'),
                      'checkInitialsUrl' => route('profile.check_initials'),
                      'pendingLabel' => __('profile_pending_label'),
                      'noPendingLabel' => __('profile_no_pending'),
                      'saveFailedLabel' => __('profile_save_failed'),
                  ]))"
                  @submit="submitting = true">
                @csrf
                @method('PATCH')

                {{-- Karte 1 · Person & Darstellung --}}
                <section class="rounded-lg border border-line-200 bg-paper-0 p-6 shadow-subtle">
                    <header class="mb-4">
                        <h2 class="text-heading font-semibold text-ink-900">{{ __('profile_card_person_title') }}</h2>
                        <p class="mt-1 text-body text-ink-500">{{ __('profile_card_person_desc') }}</p>
                    </header>

                    <div class="grid items-start gap-6 md:grid-cols-[auto_1fr]">
                        {{-- Phase 5ac.2: Avatar-Upload. Original bleibt sichtbar,
                             Live-Preview per FileReader nach Auswahl. Entfernen
                             haengt an einem hidden-Feld, damit der Save-Endpoint
                             beide Faelle sauber trennt (Upload vs. Loeschen). --}}
                        <div class="flex flex-col items-start gap-2">
                            {{-- Hintergrundfarbe nur, wenn kein Bild
                                 aktiv ist — sonst faerbt sie die Kanten
                                 hinter dem Foto. --}}
                            <div class="relative flex size-24 items-center justify-center overflow-hidden rounded-md text-heading font-semibold text-paper-0"
                                 :style="showAvatarImage ? '' : `background-color: var(--color-${currentColor})`">
                                @if ($userAvatarPath)
                                    <img x-show="!removedAvatar && !previewAvatarUrl"
                                         src="{{ asset('storage/uploads/avatars/'.$userAvatarPath) }}"
                                         alt="" class="absolute inset-0 size-full object-cover"/>
                                @endif
                                <img x-show="previewAvatarUrl" :src="previewAvatarUrl" alt="" class="absolute inset-0 size-full object-cover"/>
                                <span x-show="(removedAvatar || !previewAvatarUrl) && !hasStoredAvatar" x-text="displayInitials"></span>
                            </div>
                            <div class="flex gap-2">
                                <label class="cursor-pointer rounded-md border border-ink-300 bg-canvas-bg px-3 py-1 text-caption text-ink-900 hover:bg-line-100">
                                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                                           class="sr-only"
                                           @change="onAvatarPicked($event); dirty()"/>
                                    <span>{{ __('profile_avatar_replace') }}</span>
                                </label>
                                @if ($userAvatarPath)
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
                                         :style="`background-color: var(--color-${currentColor})`"
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
                                               x-model="initials"
                                               @input="normalizeInitials(); dirty()"
                                               @blur="checkInitialsRemote()"
                                               :aria-invalid="liveInitialsBlocked ? 'true' : null"
                                               aria-describedby="profile-initials-live-error"
                                               @class([
                                                   'w-20 rounded-md border bg-paper-0 px-3 py-2 text-center font-mono uppercase text-ink-900',
                                                   'border-danger' => $errors->has('initials'),
                                                   'border-line-200' => ! $errors->has('initials'),
                                               ])/>
                                        {{-- Q3-Politur G9 (2026-08-20) / UX-01:
                                             Live-Rueckmeldung nach Blur (parallel zum
                                             Server-Validator, der beim Save nochmal prueft). --}}
                                        <p x-show="liveInitialsBlocked && liveInitialsMessage"
                                           x-cloak
                                           id="profile-initials-live-error"
                                           class="mt-1 text-caption text-danger"
                                           x-text="liveInitialsMessage"
                                           role="status"></p>
                                        <div x-show="liveInitialsBlocked && liveInitialsSuggestions.length > 0"
                                             x-cloak
                                             class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="text-caption text-ink-500">{{ __('profile_initials_suggestions') }}</span>
                                            <template x-for="s in liveInitialsSuggestions" :key="s">
                                                <button type="button" @click="initials = s; dirty(); liveInitialsBlocked = false"
                                                        class="rounded-md border border-line-200 bg-canvas-bg px-2 py-0.5 font-mono text-caption text-ink-900 hover:bg-line-100"
                                                        x-text="s"></button>
                                            </template>
                                        </div>
                                        @error('initials')
                                            <p class="mt-1 text-caption text-danger">{{ $message }}</p>
                                            @php
                                                $suggestions = App\Support\InitialsBlocklist::suggestFor(old('firstName', $userName), old('lastName', $userLastName));
                                            @endphp
                                            @if (! empty($suggestions))
                                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                                    <span class="text-caption text-ink-500">{{ __('profile_initials_suggestions') }}</span>
                                                    @foreach ($suggestions as $suggestion)
                                                        <button type="button" @click="initials = @js($suggestion); dirty()"
                                                                class="rounded-md border border-line-200 bg-canvas-bg px-2 py-0.5 font-mono text-caption text-ink-900 hover:bg-line-100">
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
                                                        {{-- Q3-Politur G9 (2026-08-20) / UI-04:
                                                             ring-inset statt ring-offset — der aussenliegende
                                                             offset-Ring bohrte in der Palette-Reihe ein weisses
                                                             Loch pro aktiver Chip. Innen liegender Ring bleibt
                                                             innerhalb des Chips und kollidiert nicht mit den
                                                             Nachbarn. --}}
                                                        :class="currentColor === @js($token) ? 'ring-2 ring-inset ring-paper-0' : ''"
                                                        style="background-color: var(--color-{{ $token }})"
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
                                    <div class="rounded-md border border-dashed border-line-300 bg-canvas-bg px-3 py-2">
                                        <span class="block truncate text-body text-ink-900">{{ $user->email }}</span>
                                    </div>
                                    <p class="mt-1 text-caption text-ink-500">
                                        {!! __('profile_email_change_hint') !!}
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-1 text-caption font-medium text-ink-700">{{ __('profile_language') }}</p>
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

                                    <p class="mt-3 mb-1 text-caption font-medium text-ink-700">{{ __('profile_theme') }}</p>
                                    <div class="flex gap-1 rounded-md bg-line-100 p-0.5" role="tablist" aria-label="{{ __('profile_theme') }}">
                                        <button type="button" role="tab"
                                                @click="switchTheme('crowdCuratio')"
                                                :aria-selected="theme === 'crowdCuratio' ? 'true' : 'false'"
                                                :class="theme === 'crowdCuratio' ? 'bg-paper-0 text-ink-900 shadow-subtle' : 'text-ink-600'"
                                                class="flex-1 rounded-md px-3 py-1 text-caption font-medium">
                                            {{ __('profile_theme_default') }}
                                        </button>
                                        <button type="button" role="tab"
                                                @click="switchTheme('aktivesMuseum')"
                                                :aria-selected="theme === 'aktivesMuseum' ? 'true' : 'false'"
                                                :class="theme === 'aktivesMuseum' ? 'bg-paper-0 text-ink-900 shadow-subtle' : 'text-ink-600'"
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
                        <header class="mb-4">
                            <h2 class="text-heading font-semibold text-ink-900">{{ __('profile_projects_title') }}</h2>
                            <p class="mt-1 text-body text-ink-500">{{ __('profile_projects_desc') }}</p>
                        </header>

                        <ul class="divide-y divide-line-100">
                            @foreach ($profileProjects as $p)
                                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-body font-medium text-ink-900">{{ $p['name'] }}</p>
                                    </div>
                                    <span class="rounded-full bg-canvas-bg px-2 py-0.5 text-caption text-ink-700"
                                          title="{{ $p['role_desc'] }}"
                                          aria-label="{{ $p['role'] }} — {{ $p['role_desc'] }}">{{ $p['role'] }}</span>
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

                {{-- Karte 4 · Benachrichtigungen (§ 5) --}}
                <section class="mt-6 rounded-lg border border-line-200 bg-paper-0 p-6 shadow-subtle">
                    <header class="mb-4">
                        <h2 class="text-heading font-semibold text-ink-900">{{ __('profile_notify_title') }}</h2>
                        <p class="mt-1 text-body text-ink-500">{{ __('profile_notify_desc') }}</p>
                    </header>
                    <ul class="divide-y divide-line-100">
                        @foreach ([
                            ['field' => 'notify_comments', 'title' => 'profile_notify_comments_title', 'desc' => 'profile_notify_comments_desc', 'value' => (bool) $prefs->notify_comments, 'enabled' => true],
                            ['field' => 'notify_invites', 'title' => 'profile_notify_invites_title', 'desc' => 'profile_notify_invites_desc', 'value' => true, 'enabled' => false],
                            ['field' => 'notify_publish', 'title' => 'profile_notify_publish_title', 'desc' => 'profile_notify_publish_desc', 'value' => (bool) $prefs->notify_publish, 'enabled' => true],
                            ['field' => 'notify_weekly_digest', 'title' => 'profile_notify_digest_title', 'desc' => 'profile_notify_digest_desc', 'value' => (bool) $prefs->notify_weekly_digest, 'enabled' => true],
                        ] as $toggle)
                            <li class="flex items-center justify-between gap-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-body font-medium text-ink-900">{{ __($toggle['title']) }}</p>
                                    <p class="text-caption text-ink-500">{{ __($toggle['desc']) }}</p>
                                </div>
                                @if ($toggle['enabled'])
                                    <label class="inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="{{ $toggle['field'] }}" value="1"
                                               @checked($toggle['value'])
                                               @change="dirty()"
                                               class="peer sr-only"/>
                                        <span class="relative h-6 w-11 rounded-full bg-line-200 transition-colors peer-checked:bg-primary">
                                            <span class="absolute left-0.5 top-0.5 size-5 rounded-full bg-paper-0 transition-transform peer-checked:translate-x-5 shadow-subtle"></span>
                                        </span>
                                    </label>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full bg-line-100 px-3 py-1 text-caption text-ink-500">
                                        <span class="size-2 rounded-full bg-success"></span>
                                        {{ __('profile_notify_locked') }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- Sticky-Fusszeile mit Sammel-Speichern. Nennt beim Klick
                     was offen ist, damit der Nutzer nicht raetselt.
                     Q3-Politur G2 (2026-08-20) / A11Y-05: als `region` mit
                     Label, damit Screenreader die Sammel-Save-Bar
                     ansteuerbar finden. --}}
                <div class="fixed inset-x-0 bottom-0 z-20 border-t border-line-200 bg-paper-0/95 shadow-medium backdrop-blur"
                     role="region"
                     aria-label="{{ __('profile_sticky_region_label') }}">
                    <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-6 py-3">
                        <p class="text-caption text-ink-500" x-text="pendingLabel"></p>
                        <div class="flex gap-2">
                            <button type="button" @click="reset()"
                                    :disabled="!isDirty"
                                    class="rounded-md border border-ink-300 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-line-100 disabled:opacity-40">
                                {{ __('profile_discard') }}
                            </button>
                            <button type="submit"
                                    :disabled="!isDirty || submitting"
                                    class="rounded-md bg-primary px-4 py-1.5 text-caption font-semibold text-paper-0 hover:opacity-90 disabled:opacity-40">
                                <span x-show="!submitting">{{ __('save') }}</span>
                                <span x-show="submitting" x-cloak>{{ __('save') }} …</span>
                            </button>
                        </div>
                    </div>
                </div>

                <input type="hidden" x-init="locale = @js($currentLocale); theme = @js($currentTheme)"/>
            </form>
