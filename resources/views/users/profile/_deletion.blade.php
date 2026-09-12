{{--
    crowdCuratio - Curating together virtually
    Copyright (C) 2026 - berlinHistory e.V.

    Q4-Etappe 8 · I9 (2026-09-12): Sub-View aus profile.blade.php
    extrahiert. Variablen kommen ueber @include aus dem Wrapper.
--}}
            {{-- B2 (2026-08-21) / DSGVO: Reaktivierungs-Banner nach
                 Login, wenn Konto zur Loeschung angemeldet ist.  --}}
            @if (session()->has('reactivation_offer'))
                <x-ui.banner type="warning" class="mt-6">
                    {{ __('profile_reactivation_offer', ['days' => (int) session('reactivation_offer')]) }}
                    <form method="POST" action="{{ route('profile.cancel_deletion') }}" class="mt-2">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-primary px-3 py-1.5 text-caption font-semibold text-paper-0 hover:opacity-90">
                            {{ __('profile_reactivate_now') }}
                        </button>
                    </form>
                </x-ui.banner>
            @endif

            {{-- B2 (2026-08-21) / DSGVO: Konto-Loeschen-Karte.
                 Wenn eine Loeschung schon geplant ist, wird stattdessen
                 der aktuelle Zustand mit Ruecknahme-Option gezeigt.  --}}
            @php $me = auth()->user(); @endphp
            <section class="mt-6 rounded-lg border border-danger/40 bg-paper-0 p-6 shadow-subtle">
                <header class="mb-4">
                    <h2 class="text-heading font-semibold text-danger">{{ __('profile_delete_title') }}</h2>
                    <p class="mt-1 text-body text-ink-500">{{ __('profile_delete_desc', ['days' => \App\Models\User::DELETION_GRACE_DAYS]) }}</p>
                </header>

                @if ($me->isScheduledForDeletion())
                    <x-ui.banner type="danger" class="mb-4">
                        {{ __('profile_delete_scheduled_status', [
                            'date' => $me->deletionDueAt()?->format('d.m.Y'),
                            'days' => $me->deletionDaysRemaining(),
                        ]) }}
                    </x-ui.banner>
                    <form method="POST" action="{{ route('profile.cancel_deletion') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-md border border-ink-300 bg-canvas-bg px-4 py-2 text-body text-ink-900 hover:bg-line-100">
                            {{ __('profile_delete_cancel') }}
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('profile.schedule_deletion') }}"
                          x-data="{ open: false, confirmed: false }"
                          @submit="if (!confirmed) $event.preventDefault();">
                        @csrf

                        @if ($errors->has('handovers'))
                            <p class="mb-3 text-caption text-danger">{{ $errors->first('handovers') }}</p>
                        @endif

                        @if (! empty($ownedProjects))
                            <div class="mb-4 rounded-md border border-line-200 bg-canvas-bg p-4">
                                <p class="mb-2 text-caption font-semibold text-ink-700">
                                    {{ __('profile_delete_handover_hint') }}
                                </p>
                                <ul class="space-y-2">
                                    @foreach ($ownedProjects as $op)
                                        <li class="flex flex-wrap items-center gap-3">
                                            <span class="text-body text-ink-900">{{ $op->name }}</span>
                                            <span class="text-caption text-ink-500">→</span>
                                            <select name="handovers[{{ $op->id }}]" required
                                                    class="rounded-md border border-line-200 bg-paper-0 px-2 py-1 text-body">
                                                <option value="">{{ __('profile_delete_handover_placeholder') }}</option>
                                                @foreach ($handoverCandidates as $cand)
                                                    <option value="{{ $cand->id }}">{{ trim($cand->name.' '.$cand->last_name) }} · {{ $cand->email }}</option>
                                                @endforeach
                                            </select>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="mb-1 block text-caption font-medium text-ink-700" for="deletion-reason">
                                {{ __('profile_delete_reason_label') }}
                            </label>
                            <input id="deletion-reason" name="reason" type="text" maxlength="255"
                                   class="w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body"/>
                        </div>

                        <label class="flex items-start gap-2 text-body text-ink-700">
                            <input type="checkbox" name="confirm" value="1" required
                                   x-model="confirmed"
                                   class="mt-1 size-4 rounded border-form-border text-danger focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-danger"/>
                            <span>{{ __('profile_delete_confirm_label', ['days' => \App\Models\User::DELETION_GRACE_DAYS]) }}</span>
                        </label>

                        <div class="mt-4 flex justify-end">
                            <button type="submit"
                                    :disabled="!confirmed"
                                    class="inline-flex items-center rounded-md bg-danger px-4 py-2 text-body font-semibold text-paper-0 hover:opacity-90 disabled:opacity-40">
                                {{ __('profile_delete_submit') }}
                            </button>
                        </div>
                    </form>
                @endif
            </section>
