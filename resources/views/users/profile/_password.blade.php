{{--
    crowdCuratio - Curating together virtually
    Copyright (C) 2026 - berlinHistory e.V.

    Q4-Etappe 8 · I9 (2026-09-12): Sub-View aus profile.blade.php
    extrahiert. Variablen kommen ueber @include aus dem Wrapper.
--}}
            <form method="POST" action="{{ route('profile.password') }}"
                  id="password"
                  class="mt-6 scroll-mt-6"
                  x-data="ccPasswordCard()"
                  @submit="submitting = true">
                @csrf
                @method('PATCH')
                <section class="rounded-lg border border-line-200 bg-paper-0 p-6 shadow-subtle">
                    <header class="mb-4">
                        <h2 class="text-heading font-semibold text-ink-900">{{ __('profile_password_title') }}</h2>
                    </header>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-caption font-medium text-ink-700" for="pw-old">{{ __('profile_password_old') }}</label>
                            <input id="pw-old" name="old_password" type="password" autocomplete="current-password"
                                   class="w-full rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
                            @error('old_password') <p class="mt-1 text-caption text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-caption font-medium text-ink-700" for="pw-new">{{ __('profile_password_new') }}</label>
                            <input id="pw-new" name="new_password" type="password" autocomplete="new-password"
                                   x-model="pw" minlength="10" required
                                   class="w-full rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
                            @error('new_password') <p class="mt-1 text-caption text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-caption font-medium text-ink-700" for="pw-confirm">{{ __('profile_password_confirm') }}</label>
                            <input id="pw-confirm" name="confirm_password" type="password" autocomplete="new-password"
                                   x-model="confirm" required
                                   class="w-full rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
                            @error('confirm_password') <p class="mt-1 text-caption text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="flex h-2 gap-1">
                            <template x-for="i in 4" :key="i">
                                <div class="h-full flex-1 rounded" :class="strength >= i ? 'bg-success' : 'bg-line-200'"></div>
                            </template>
                        </div>
                        <p class="mt-1 flex justify-between text-caption text-ink-500">
                            <span x-text="strengthLabel"></span>
                            <span>{{ __('profile_password_rule_hint') }}</span>
                        </p>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-caption text-ink-500">{{ __('profile_password_session_hint') }}</p>
                        <button type="submit"
                                :disabled="submitting"
                                class="rounded-md border border-ink-300 bg-canvas-bg px-4 py-1.5 text-caption font-semibold text-ink-900 hover:bg-line-100 disabled:opacity-40">
                            <span x-show="!submitting">{{ __('profile_password_save') }}</span>
                            <span x-show="submitting" x-cloak>{{ __('profile_password_save') }} …</span>
                        </button>
                    </div>
                </section>
            </form>
