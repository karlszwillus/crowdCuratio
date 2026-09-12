{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 8 · E3e (2026-09-12): Rollen-Übersicht von Bootstrap-3
auf Tailwind + Alpine umgezogen. Das alte jQuery-Modal
(`#roleModal.modal('show')`) läuft jetzt als Alpine-`x-data`-
Overlay, damit kein jQuery-Plugin mehr nötig ist.
--}}

@extends('projects.layout')

@section('main')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h2 class="text-title font-semibold text-ink-900">{{ __('role_management') }}</h2>
        @hasPermissionTo('add')
            <a href="{{ route('roles.create') }}"
               class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                <x-icon name="plus" size="4"/>
                {{ __('create_new_role') }}
            </a>
        @endhasPermissionTo
    </div>

    @if ($message = Session::get('success'))
        <x-ui.banner type="success" class="mb-4" dismissible>{{ $message }}</x-ui.banner>
    @endif

    {{-- Alpine-basiertes Lösch-Modal: statt jQuery `.modal('show')`
         schaltet ein zentraler x-data-Wrapper das Overlay per State. --}}
    <div x-data="{
             open: false,
             deletedRole: null,
             alternativeRole: '',
             action: '',
             deleteUrlTemplate: @js(route('customizedDelete', [':id', ':alt'])),
             roles: @js($roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])),
             showDialog(id) {
                 this.deletedRole = id;
                 this.alternativeRole = '';
                 this.action = this.deleteUrlTemplate.replace(':id', id).replace(':alt', '');
                 this.open = true;
             },
             pickAlternative(alt) {
                 this.alternativeRole = alt;
                 this.action = this.deleteUrlTemplate.replace(':id', this.deletedRole).replace(':alt', alt);
             },
             get otherRoles() {
                 return this.roles.filter(r => r.id !== this.deletedRole);
             }
         }">

        <table class="min-w-full divide-y divide-line-100 border border-line-200 rounded-md overflow-hidden">
            <thead class="bg-canvas-bg text-left text-caption font-medium uppercase tracking-wider text-ink-700">
                <tr>
                    <th class="px-4 py-2">{{ __('name') }}</th>
                    <th class="px-4 py-2 text-right">{{ __('action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line-100 bg-paper-0">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-2 text-body text-ink-900">{{ $role->name }}</td>
                        <td class="px-4 py-2 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('roles.show', $role->id) }}"
                                   title="{{ __('view_role') }}"
                                   class="inline-flex size-8 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900">
                                    <x-icon name="eye" size="4"/>
                                </a>
                                @if ($role->name !== 'Admin')
                                    @hasPermissionTo('edit')
                                        <a href="{{ route('roles.edit', $role->id) }}"
                                           title="{{ __('edit_role') }}"
                                           class="inline-flex size-8 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900">
                                            <x-icon name="pencil" size="4"/>
                                        </a>
                                    @endhasPermissionTo
                                    @if (auth()->user()->id !== $role->id)
                                        @hasPermissionTo('delete')
                                            @if ($role->cnt > 0)
                                                <button type="button"
                                                        @click="showDialog({{ $role->id }})"
                                                        title="{{ __('delete_role') }}"
                                                        class="inline-flex size-8 items-center justify-center rounded-md text-danger hover:bg-danger-bg">
                                                    <x-icon name="trash-2" size="4"/>
                                                </button>
                                            @else
                                                <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="inline-flex">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            onclick="return confirm('{{ __('message_delete_confirm') }}')"
                                                            title="{{ __('delete_role') }}"
                                                            class="inline-flex size-8 items-center justify-center rounded-md text-danger hover:bg-danger-bg">
                                                        <x-icon name="trash-2" size="4"/>
                                                    </button>
                                                </form>
                                            @endif
                                        @endhasPermissionTo
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Lösch-Alternative-Modal (Alpine). --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4"
             @keydown.escape.window="open = false"
             @click.self="open = false"
             role="dialog"
             aria-modal="true">
            <div class="w-full max-w-md rounded-lg border border-line-200 bg-paper-0 shadow-lg">
                <header class="flex items-center justify-between border-b border-line-200 px-5 py-3">
                    <h3 class="text-heading font-semibold text-ink-900">{{ __('delete_role') }}</h3>
                    <button type="button" @click="open = false"
                            aria-label="{{ __('close') }}"
                            class="rounded-md p-1 text-ink-500 hover:bg-line-100 hover:text-ink-900">
                        <x-icon name="x" size="4"/>
                    </button>
                </header>

                <form :action="action" method="POST" class="px-5 py-4">
                    @csrf
                    <input type="hidden" name="alternativeRole" :value="alternativeRole"/>
                    <input type="hidden" name="deletedRole" :value="deletedRole"/>

                    <p class="mb-3 text-body text-ink-700">{{ __('role_delete_pick_alternative') }}</p>
                    <label class="mb-1 block text-caption font-medium text-ink-700" for="roleAlternative">
                        {{ __('role') }}
                    </label>
                    <select id="roleAlternative" @change="pickAlternative($event.target.value)"
                            class="w-full rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900">
                        <option value="">{{ __('role_delete_choose_placeholder') }}</option>
                        <template x-for="r in otherRoles" :key="r.id">
                            <option :value="r.id" x-text="r.name"></option>
                        </template>
                    </select>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="open = false"
                                class="inline-flex items-center rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 hover:bg-line-100">
                            {{ __('cancel') }}
                        </button>
                        <button type="submit"
                                :disabled="!alternativeRole"
                                class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90 disabled:opacity-40">
                            {{ __('delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
