<!--
crowdCuratio - Curating together virtually
Copyright (C)2022 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>. -->

@extends('projects.layout')
@once
    @push('scripts')

    @endpush
@endonce
@section('log')
    <livewire:sidebar-tree :project="$project" :key="'sidebar-tree-'.$project->id" />
@endsection

@section('sidebar')

    @include('projects.description')

@endsection

@section('main')
    {{-- Tree-Daten für die Live-Breadcrumb. Die <x-ui.breadcrumb>-
         Komponente watcht im :tree-Modus window.location.hash und
         leitet daraus den Pfad ab — Klick im Sidebar-Tree ändert
         den Hash, Breadcrumb folgt automatisch. ProjectTreeService
         ist die Single Source of Truth (siehe SidebarTree-Volt). --}}
    <x-ui.breadcrumb :tree="app(App\Services\ProjectTreeService::class)->breadcrumbTree($data)" />

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>{{__('whoops')}}</strong> {{__('message_problem_input')}}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="row border p-2 mb-4">
        <form action="{{ route('projects.destroy',$project->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="col-sm-3">
                @if(Auth::user()->can('update', $project) || Auth::user()->can('delete'))
                    <button class="btn btn-secondary btn-block text-left mt-1 mb-2" type="submit"
                            onclick="return confirm('{{__('message_delete_confirm')}}')">
                        <i class="bi bi-trash m-2"></i> {{__('delete_project')}}
                    </button>
                @endif
            </div>
            {{-- Reader-Frontend-Härtung Juni 2026: Backend blockt
                 jetzt sowohl `translate` (translateCurrentProject)
                 als auch `project.metadata` (editMetaData) via
                 ProjectPolicy::update. Frontend zeigte die Buttons
                 aber weiter dem Reader an — das gab im Smoke einen
                 toten Klick-Pfad (403 oder weisse Seite). Buttons
                 hinter @can('update', $project), damit sie nur
                 Owner/Admin/Eingeladener-mit-edit sehen. --}}
            @can('update', $project)
                <div class="col-sm-3">
                    <a href="{{route('translate', $project->id)}}"
                       class="btn btn-secondary btn-block text-left mt-1 mb-2">{{__('translate')}}</a>
                </div>
                <div class="col-sm-3">
                    <a href="{{route('project.metadata', $project->id)}}"
                       class="btn btn-secondary btn-block text-left mt-1 mb-2">{{__('meta_data')}}</a>
                </div>
            @endcan
        </form>

    </div>
    @if(isset($data) /**&& count($data) > 0*/)
        <div class="row project mb-4">
            <div class="col-sm-2">
                @if($project->logo) <img src="{{route('image', $project->logo)}}" alt="{{$project->logo}}" class="logo"> @endif
            </div>
            <div class="col-sm-9">
                <h1>{{$project->name}}</h1>
                <p>{!! $project->description !!}</p>
            </div>
        </div>
        <ul class="list-group ui-sortable-chapter sortable_list_chapter connectedSortableChapter" id="groupsList" data-reorder-element="chapter" data-reorder-url="{{ route('chapter.drag') }}" data-reorder-project="{{ $project->id }}">
            @foreach($data->chapters as $key => $chapter)
                <li class="chapter group" data-chapter="{{$chapter->id}}" data-project="{{$project->id}}" id="{{$chapter->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                    <div id="{{$chapter->id}}">
                        <div class="row border border-secondary p-4 mb-4 content">
                            <div style="float: left;" id="anchor_Chapter_{{$chapter->id}}">
                                @can('update', $project)
                                    <livewire:inline-editor
                                        :model="$chapter"
                                        field="name"
                                        rules="nullable|string|max:255"
                                        :label="__('chapter_title')"
                                        :key="'chapter-name-'.$chapter->id"
                                    />
                                    <livewire:inline-editor
                                        :model="$chapter"
                                        field="subtitle"
                                        rules="nullable|string|max:255"
                                        :label="__('chapter_subtitle')"
                                        :key="'chapter-subtitle-'.$chapter->id"
                                    />
                                    <livewire:rich-text-editor
                                        :model="$chapter"
                                        field="description"
                                        rules="nullable|string"
                                        :label="__('chapter_description')"
                                        :key="'chapter-description-'.$chapter->id"
                                    />
                                @else
                                    <h2>{!! $chapter->name !!}</h2>
                                    <p>{!! $chapter->subtitle !!}</p>
                                    <p>{!! $chapter->description !!}</p>
                                @endcan
                            </div>
                            <div class="ml-auto mr-3 icons">
                                <form action="{{ route('chapters.destroy',$chapter->id) }}" method="POST"
                                      class="mb-5">
                                    @csrf
                                    <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                    @method('DELETE')

									<span data-toggle="tooltip"
                                              data-placement="top"
                                              title="ältere Versionen"><a
                                                    href="{{route('projects.edit',['project'=> $project, 'log'=> $chapter->id, 'model' => 'Chapter'])}}"
                                                    class="text-log"><i
                                                        class="bi bi-clock-history m-2"></i></a></span>

                                    @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                        <span data-toggle="tooltip" data-placement="top"
                                              title="{{__('add_comment')}}"><a href="{{route('projects.edit', ['project'=> $project,'model'=> 'App\Models\Chapter', 'comment' => $chapter->id])}}" class="addComment"> @if(isset($chapter->comments) && count($chapter->comments) > 0)
                                                    <i class="bi bi-chat-dots-fill m-2"></i> @else <i class="bi bi-chat m-2"></i>@endif
											</a></span>
                                    @endif
									@if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                        <button type="submit" onclick="return confirm('{{__('message_delete_confirm')}}')"
                                                data-toggle="tooltip" data-placement="top" title="{{__('delete_chapter')}}">
											<i class="bi-x-circle-fill m-2"></i></button>
									@endif
									{{-- Edit-Button für Chapter entfällt: Titel, Subtitel und
									     Beschreibung werden per inline-editor-Volt-Komponente
									     direkt im Kapitel-Card editiert (Phase 5c.6.a). Add-Modal
									     (myModal) bleibt für „Kapitel hinzufügen". --}}
									<a onclick="collapseExpand({{$chapter->id}})"  id="{{$chapter->id}}"
                                       aria-expanded="true" aria-controls="collapseChapter_{{$chapter->id}}"><i
                                                class="bi-caret-down-fill" id="chp_{{$chapter->id}}"></i></a>
								</form>
								<p class="date">{!! date('d.m.Y', strtotime($chapter->created_at)) !!}</p>
                            </div>
                        </div>
                        <div class="collapse in" id="chapter_{{$chapter->id}}" aria-expanded="false">
                            @if(isset($chapter->entries) && count($chapter->entries) >0)
                                <ul class="list-group ui-sortable-entry sortable_list_entry connectedSortableEntry" id="{{$chapter->id}}" data-reorder-element="entry" data-reorder-url="{{ route('chapter.drag') }}">
                                    @foreach($chapter->entries as $entry)
                                        <li class="entry group" data-chapter="{{$chapter->id}}" data-entry="{{$entry->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                <div id="P-{{$project->id}}-C-{{$chapter->id}}-entry-{{$entry->id}}"
                                                             class="row border border-secondary p-4 mb-4 ml-auto w-11/12 content">
                                                            <div style="float: left;" id="anchor_Entry_{{$entry->id}}">
                                                                @can('update', $project)
                                                                    <livewire:inline-editor
                                                                        :model="$entry"
                                                                        field="name"
                                                                        rules="nullable|string|max:255"
                                                                        :label="__('entry_title')"
                                                                        :key="'entry-name-'.$entry->id"
                                                                    />
                                                                    <livewire:inline-editor
                                                                        :model="$entry"
                                                                        field="subtitle"
                                                                        rules="nullable|string|max:255"
                                                                        :label="__('entry_subtitle')"
                                                                        :key="'entry-subtitle-'.$entry->id"
                                                                    />
                                                                    <livewire:rich-text-editor
                                                                        :model="$entry"
                                                                        field="description"
                                                                        rules="nullable|string"
                                                                        :label="__('entry_description')"
                                                                        :key="'entry-description-'.$entry->id"
                                                                    />
                                                                @else
                                                                    <h3>{!! $entry->name !!}</h3>
                                                                    <p>{!! $entry->subtitle !!}</p>
                                                                    <p>{!! $entry->description !!}</p>
                                                                @endcan
                                                            </div>
                                                            <div class="ml-auto mr-3 icons">
                                                                <form action="{{ route('entries.destroy',$entry->id) }}"
                                                                      method="POST"
                                                                      class="mb-5">
                                                                    @csrf
                                                                    <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                    @method('DELETE')

                                                                    <span data-toggle="tooltip"
                                                                              data-placement="top"
                                                                              title="ältere Versionen"><a
                                                                                    href="{{route('projects.edit',['project'=> $project, 'log'=> $entry->id, 'model' => 'Entry'])}}"
                                                                                    class="text-log"><i
                                                                                        class="bi bi-clock-history m-2"></i></a></span>

                                                                    @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                        <span data-toggle="tooltip" data-placement="top"
                                                                              title="{{__('add_comment')}}"><a href="{{route('projects.edit', ['project'=> $project,'model'=> 'App\Models\Entry', 'comment' => $entry->id])}}"> @if(isset($entry->comments) && count($entry->comments) > 0)
                                                                                    <i class="bi bi-chat-dots-fill m-2"></i>@else
                                                                                    <i class="bi bi-chat m-2"></i>@endif
																			</a></span>
                                                                    @endif

 																	@if(in_array('edit', $listPermissions) || Auth::user()->can('delete', $project))
                                                                        <button type="submit"
                                                                                onclick="return confirm('{{__('message_delete_confirm')}}')"
                                                                                data-toggle="tooltip" data-placement="top"
                                                                                title="{{__('delete_entry')}}">
																			<i class="bi-x-circle-fill m-2"></i>
																	</button>
                                                                    @endif

                                                                    {{-- Edit-Button für Entry entfällt: Titel, Subtitel und
                                                                         Beschreibung werden per inline-editor-Volt-Komponente
                                                                         direkt im Entry-Card editiert (Phase 5c.6.b).
                                                                         Add-Entry-Modal (entryModal) bleibt für „Eintrag
                                                                         hinzufügen". --}}


                                                                    <a onclick="collapseExpandEntry({{$entry->id}})" class="panel-heading "
                                                                       role="button" aria-expanded="true" aria-controls="entry_{{$entry->id}}" ><i
                                                                                class="bi-caret-down-fill" id="ent_{{$entry->id}}"></i></a>
																</form>
                                                                <p class="date">{!! date('d.m.Y', strtotime($entry->created_at)) !!}</p>
                                                            </div>
                                                        </div>
                                                    @if(isset($entry->mediaContent) && count($entry->mediaContent) > 0)
                                                        <div id="entry_{{$entry->id}}">
                                                            <ul class="list-group  ui-sortable-content sortable_list_content connectedSortableContent" data-entry="{{$entry->id}}" id="{{$entry->id}}" data-reorder-element="content" data-reorder-url="{{ route('chapter.drag') }}">
                                                                @foreach($entry->mediaContent as $item)
                                                                    @if($item->content_type == 'App\Models\Text')
                                                                        @isset($item->text->text)
                                                                            <li class="item text content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                                                <div class="row border border-secondary p-4 mb-4 ml-auto w-10/12">
                                                                                    <div id="anchor_MediaContent_{{$item->id}}">
                                                                                        <div class="text-scrollbar overflow-auto">
                                                                                            @can('update', $project)
                                                                                                <livewire:rich-text-editor
                                                                                                    :model="$item->text"
                                                                                                    field="text"
                                                                                                    rules="nullable|string"
                                                                                                    :label="__('text_content')"
                                                                                                    :key="'text-content-'.$item->text->id" />
                                                                                            @else
                                                                                                <p>{!! html_entity_decode($item->text->text) !!}</p>
                                                                                            @endcan
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="text-right icons">
                                                                                        <form action="{{ route('text.delete',$item->text->id) }}"
                                                                                              method="POST" class="mb-5">
                                                                                            @csrf
                                                                                            <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                                            @method('DELETE')
                                                                                            <span data-toggle="tooltip"
                                                                                                      data-placement="top"
                                                                                                      title="ältere Versionen"><a
                                                                                                            href="{{route('projects.edit',['project'=> $project, 'log'=> $item->text->id, 'model' => 'Text'])}}"
                                                                                                            class="text-log"><i
                                                                                                                class="bi bi-clock-history m-2"></i></a></span>
                                                                                            @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                                <span data-toggle="tooltip"
                                                                                                      data-placement="top"
                                                                                                      title="{{__('add_comment')}}"><a
                                                                                                            href="{{route('projects.edit', ['project'=> $project,'model'=> 'App\Models\Text', 'comment' => $item->text->id, 'type' => 'Text'])}}"> @if(isset($item->text->comments) && count($item->text->comments) > 0)
                                                                                                            <i class="bi bi-chat-dots-fill m-2"></i> @else
                                                                                                            <i class="bi bi-chat m-2"></i>@endif
																									</a></span>
																							@endif

 																								@if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                                <button type="submit"
                                                                                                        onclick="return confirm('{{__('message_delete_confirm')}}')">
                                                                                                    <i class="bi-x-circle-fill m-2"
                                                                                                       data-toggle="tooltip"
                                                                                                       data-placement="top"
                                                                                                       title="{{__('delete_text')}}"></i>
                                                                                                </button>
                                                                                            @endif

                                                                                            {{-- Edit-Button für Text entfällt seit Phase 5c.6.c.4:
                                                                                                 Text-Content wird per rich-text-editor-Volt-Komponente
                                                                                                 direkt im Content-Card editiert. Add-Text-Modal
                                                                                                 (contentModal) bleibt für „Text hinzufügen". --}}
                                                                                        </form>
                                                                                        {{-- Metadaten hinter <details>-Toggle.
                                                                                             Copyright/Quelle als inline source-picker
                                                                                             mit Autocomplete + „Neu anlegen" — löst
                                                                                             den letzten Modal für Text-Content ab
                                                                                             (Phase 5c.6.c.4-Followup). --}}
                                                                                        <details class="metadata mt-2 rounded-md border border-ink-300/60 bg-canvas-dim/40 px-3 py-2">
                                                                                            <summary class="cursor-pointer select-none text-caption text-chrome-on-dim focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                                                                {{ __('metadata') }}
                                                                                            </summary>
                                                                                            <div class="mt-2 space-y-2 text-caption text-chrome-on-dim">
                                                                                                <div>{!! date('d.m.Y', strtotime($item->text->created_at)) !!}</div>
                                                                                                @can('update', $project)
                                                                                                    <livewire:source-picker
                                                                                                        :model="$item->text"
                                                                                                        field="copyright"
                                                                                                        relation="copyrightText"
                                                                                                        source-type="Copyright"
                                                                                                        :label="__('copyright')"
                                                                                                        :key="'text-copyright-'.$item->text->id" />
                                                                                                    <livewire:source-picker
                                                                                                        :model="$item->text"
                                                                                                        field="origin"
                                                                                                        relation="originText"
                                                                                                        source-type="Origin"
                                                                                                        :label="__('origin')"
                                                                                                        :key="'text-origin-'.$item->text->id" />
                                                                                                @else
                                                                                                    <div>Copyright: {!! $item->text->copyrightText?->name !!}</div>
                                                                                                    <div>{{ __('origin') }}: {!! $item->text->originText?->name !!}</div>
                                                                                                @endcan
                                                                                            </div>
                                                                                        </details>
                                                                                    </div>
                                                                                </div>
                                                                            </li>
                                                                        @endisset
                                                                    @endif
                                                                    @if($item->content_type == 'App\Models\Audiovisual')
                                                                        @isset($item->audiovisual->link)
                                                                            <li class="item audiovisual content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                                                <div class="row border border-secondary p-4 mb-4 ml-auto w-10/12">
                                                                                    <div id="anchor_MediaContent_{{$item->id}}">
                                                                                        {{-- Player als eigenständige Volt-Komponente
                                                                                             (Phase 5c.6.c.3). Rendert audio/iframe
                                                                                             und aktualisiert sich beim Speichern
                                                                                             eines Inline-Editor-Felds via `saved`
                                                                                             Event. --}}
                                                                                        <livewire:audiovisual-player
                                                                                            :audiovisual="$item->audiovisual"
                                                                                            :key="'av-player-'.$item->audiovisual->id" />

                                                                                        @can('update', $project)
                                                                                            <div class="mt-3 space-y-2">
                                                                                                {{-- Link/Uploader rendert jetzt der
                                                                                                     audiovisual-player selbst
                                                                                                     (Phase 5c.6.c.3-Fix); der
                                                                                                     Type-Wechsel triggert dort
                                                                                                     den kompletten Re-Render. --}}
                                                                                                <livewire:inline-editor
                                                                                                    :model="$item->audiovisual"
                                                                                                    field="type"
                                                                                                    rules="required|in:audio,video"
                                                                                                    :options="['audio' => __('audio'), 'video' => __('video')]"
                                                                                                    :label="__('type')"
                                                                                                    :key="'av-type-'.$item->audiovisual->id" />

                                                                                                {{-- Copyright und Quelle sind
                                                                                                     Metadaten, keine Player-Konfig
                                                                                                     — hinter einem <details>-Toggle
                                                                                                     kollabiert, damit die Editor-
                                                                                                     Sicht ruhig bleibt. Nativ,
                                                                                                     ohne JS, WCAG-freundlich. --}}
                                                                                                <details class="mt-1 rounded-md border border-ink-300/60 bg-canvas-dim/40 px-3 py-2">
                                                                                                    <summary class="cursor-pointer select-none text-caption text-chrome-on-dim focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                                                                        {{ __('metadata') }}
                                                                                                    </summary>
                                                                                                    <div class="mt-2 space-y-2">
                                                                                                        <livewire:inline-editor
                                                                                                            :model="$item->audiovisual"
                                                                                                            field="copyright"
                                                                                                            rules="nullable|string|max:255"
                                                                                                            :label="__('copyright')"
                                                                                                            :key="'av-copyright-'.$item->audiovisual->id" />
                                                                                                        <livewire:inline-editor
                                                                                                            :model="$item->audiovisual"
                                                                                                            field="source"
                                                                                                            rules="nullable|string|max:255"
                                                                                                            :label="__('origin')"
                                                                                                            :key="'av-source-'.$item->audiovisual->id" />
                                                                                                    </div>
                                                                                                </details>
                                                                                            </div>
                                                                                        @else
                                                                                            <p class="metadata mt-2">
                                                                                                Copyright {!! $item->audiovisual->copyright !!}<br>
                                                                                                Origin {!! $item->audiovisual->source !!}
                                                                                            </p>
                                                                                        @endcan
                                                                                    </div>
                                                                                    <div class="text-right icons">
                                                                                        <form action="{{ route('audiovisual.delete',$item->audiovisual->id) }}"
                                                                                              method="POST" class="mb-5">
                                                                                            @csrf
                                                                                            <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                                            @method('DELETE')
                                                                                           <span data-toggle="tooltip"
                                                                                                      data-placement="top"
                                                                                                      title="ältere Versionen"><a
                                                                                                            href="{{route('projects.edit',['project'=> $project, 'log'=> $item->audiovisual->id, 'model' => 'Audiovisual'])}}"
                                                                                                            class="text-log"><i
                                                                                                                class="bi bi-clock-history m-2"></i></a></span>

                                                                                            @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                                <span data-toggle="tooltip"
                                                                                                      data-placement="top"
                                                                                                      title="{{__('add_comment')}}"><a
                                                                                                            href="{{route('projects.edit', ['project'=> $project,'model'=> 'App\Models\Audiovisual', 'comment' => $item->audiovisual->id, 'type' => 'Audiovisual'])}}"> @if(isset($item->audiovisual->comments) && count($item->audiovisual->comments) > 0)
                                                                                                            <i class="bi bi-chat-dots-fill m-2"></i> @else
                                                                                                            <i
                                                                                                                    class="bi bi-chat m-2"></i> @endif </a></span>
                                                                                            @endif

 																							@if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                                <button type="submit"
                                                                                                        onclick="return confirm('{{__('message_delete_confirm')}}')">
                                                                                                    <i class="bi-x-circle-fill m-2"
                                                                                                       data-toggle="tooltip"
                                                                                                       data-placement="top"
                                                                                                       title="{{__('delete_text')}}"></i>
                                                                                                </button>
                                                                                            @endif

                                                                                            {{-- Modify-Button für Audiovisual entfällt seit
                                                                                                 Phase 5c.6.c.3: link/type/copyright/source
                                                                                                 werden per inline-editor-Volt-Komponente
                                                                                                 direkt unter dem Player editiert. Add-
                                                                                                 Audiovisual-Modal (audiovisualModal) bleibt
                                                                                                 für neu Anlegen. --}}
                                                                                        </form>
                                                                                        <p class="metadata">
                                                                                            {!! date('d.m.Y', strtotime($item->audiovisual->created_at)) !!}
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                            </li>
                                                                        @endisset
                                                                    @endif
                                                                    {{-- Phase 4 / E.7b 4a: alte Spalte hatte historisch
                                                                         'App\Models\Image' für Galleries; neue content_type
                                                                         hat 'App\Models\Gallery' (ADR-0022). --}}
                                                                    @if(isset($item) && $item->content_type == 'App\Models\Gallery')
                                                                        @if(isset($item->gallery))
                                                                            <li class="item gallery content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                                                <div class="row border border-secondary p-4 mb-4 ml-auto w-10/12">
                                                                                    <div class="row">
                                                                                        <div class="">
                                                                                            @can('update', $project)
                                                                                                <livewire:inline-editor
                                                                                                    :model="$item->gallery"
                                                                                                    field="title"
                                                                                                    rules="nullable|string|max:255"
                                                                                                    :label="__('title')"
                                                                                                    :key="'gallery-title-'.$item->gallery->id"
                                                                                                />
                                                                                                <livewire:inline-editor
                                                                                                    :model="$item->gallery"
                                                                                                    field="subtitle"
                                                                                                    rules="nullable|string|max:255"
                                                                                                    :key="'gallery-subtitle-'.$item->gallery->id"
                                                                                                />
                                                                                                <livewire:rich-text-editor
                                                                                                    :model="$item->gallery"
                                                                                                    field="description"
                                                                                                    rules="nullable|string"
                                                                                                    :label="__('gallery_description')"
                                                                                                    :key="'gallery-description-'.$item->gallery->id"
                                                                                                />
                                                                                            @else
                                                                                                <h4>{{$item->gallery->title}}</h4>
                                                                                                <p>{{$item->gallery->subtitle}}</p>
                                                                                                <p>{{$item->gallery->description}}</p>
                                                                                            @endcan
                                                                                        </div>
                                                                                        <div class="text-right icons">
                                                                                            <form action="{{ route('gallery.delete',$item->gallery->id) }}"
                                                                                                  method="POST" class="mb-5">
                                                                                                @csrf
                                                                                                <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                                                @method('DELETE')

                                                                                                <span data-toggle="tooltip"
                                                                                                          data-placement="top"
                                                                                                          title="ältere Versionen"><a
                                                                                                                href="{{route('projects.edit',['project'=> $project, 'log'=> $item->gallery->id, 'model' => 'Gallery'])}}"
                                                                                                                class="text-log"><i
                                                                                                                    class="bi bi-clock-history m-2"></i></a></span>

                                                                                                @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                                    <span data-toggle="tooltip"
                                                                                                          data-placement="top"
                                                                                                          title="{{__('add_comment')}}"><a
                                                                                                                href="{{route('projects.edit', ['project'=> $project,'model'=> 'App\Models\Gallery', 'comment' => $item->gallery->id, 'type'=> 'Gallery'])}}" > @if(isset($item->gallery->comments) && count($item->gallery->comments) > 0)
                                                                                                                <i class="bi bi-chat-dots-fill m-2"></i> @else
                                                                                                                <i
                                                                                                                        class="bi bi-chat m-2"></i> @endif </a></span>
                                                                                                @endif

 																								@if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                                                                                                    <span data-toggle="tooltip"
                                                                                                          data-placement="top"
                                                                                                          title="{{__('add_content')}}"> <button
                                                                                                                type="button"
                                                                                                                class="addImage"
                                                                                                                data-chapter="{{$chapter->name}}"
                                                                                                                data-entry="{{$entry->name}}"
                                                                                                                data-id="{{$item->gallery->id}}"
                                                                                                                data-entryId="{{$entry->id}}"
                                                                                                                data-toggle="modal"
                                                                                                                data-target="#imageModal"> <i
                                                                                                                    class="bi bi-plus-circle m-2"></i> </button></span>
                                                                                                @endif
                                                                                                @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                                    <button type="submit"
                                                                                                            onclick="return confirm('{{__('message_delete_confirm')}}')">
                                                                                                        <i class="bi-x-circle-fill m-2"
                                                                                                           data-toggle="tooltip"
                                                                                                           data-placement="top"
                                                                                                           title="{{__('delete_image')}}"></i>
                                                                                                    </button>
                                                                                                @endif
                                                                                                {{-- Gallery-Edit-Modal-Trigger entfällt seit 5c.6.c:
                                                                                                     Title/Subtitle/Description werden inline editiert.
                                                                                                     Bild-Hinzufügen (addImage) bleibt modal. --}}
                                                                                            </form>
                                                                                        </div>
                                                                                    </div><div class="gallery_container">
                                                                                    @foreach($item->gallery->images as $image)
                                                                                        <div class="row mt-4 gallery_item" id="gallery_items_{{$item->gallery->id}}">
                                                                                            {{-- Höhe zusätzlich als Utility, weil die alte
                                                                                                 crowdcuratio.css-Regel `.gallery_item .img
                                                                                                 { height: 300px }` unter Tailwind 4 Preflight
                                                                                                 nicht mehr zuverlässig greift und das Div
                                                                                                 sonst auf Caption-Höhe kollabiert. --}}
                                                                                            <div id="anchor_MediaContent_{{$item->id}}"
                                                                                                 class="img relative h-[300px] w-full bg-cover bg-center bg-no-repeat"
                                                                                                 style="background-image: url('{{route('image', $image->image)}}')" >
                                                                                               <div class="caption">
                                                                                                    @can('update', $project)
                                                                                                        <livewire:inline-editor
                                                                                                            :model="$image"
                                                                                                            field="alt"
                                                                                                            rules="nullable|string|max:255"
                                                                                                            :key="'image-alt-'.$image->id"
                                                                                                        />
                                                                                                    @else
                                                                                                        {{$image->alt}}
                                                                                                    @endcan
                                                                                                </div>
																								{{-- <img src="{{route('image', $image->image)}}" alt="{{$item->alt}}" style=""> --}}

                                                                                            </div>
                                                                                            <div class="text-right icons">
                                                                                                <form action="{{ route('image.delete',$image->id) }}"
                                                                                                      method="POST" class="mb-5">
                                                                                                    @csrf
                                                                                                    @method('DELETE')
																									<span data-toggle="tooltip"
                                                                                                              data-placement="top"
                                                                                                              title="ältere Versionen"><a
                                                                                                                    href="{{route('projects.edit',['project'=> $project, 'log'=> $image->id, 'model' => 'Image'])}}"
                                                                                                                    class="text-log"><i
                                                                                                                        class="bi bi-clock-history m-2"></i></a></span>

                                                                                                    @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                                        <span data-toggle="tooltip"
                                                                                                              data-placement="top"
                                                                                                              title="{{__('add_comment')}}"><a
                                                                                                                    href="{{route('projects.edit', ['project'=> $project,'model'=> 'App\Models\Image', 'comment' => $image->id, 'type'=> 'Image'])}}"> @if(isset($image->comments) && count($image->comments) > 0)
                                                                                                                    <i class="bi bi-chat-dots-fill m-2"></i> @else
                                                                                                                    <i class="bi bi-chat m-2"></i> @endif </a></span>
                                                                                                    @endif

                                                                                                    @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                                        <button type="submit"
                                                                                                                onclick="return confirm('{{__('message_delete_confirm')}}')">
                                                                                                            <i class="bi-x-circle-fill m-2"
                                                                                                               data-toggle="tooltip"
                                                                                                               data-placement="top"
                                                                                                               title="{{__('delete_image')}}"></i>
                                                                                                        </button>
                                                                                                    @endif

                                                                                                    {{-- Modify-Button für Image entfällt seit
                                                                                                         Phase 5c.6.c.4-Followup — Copyright/
                                                                                                         Quelle werden über den source-picker
                                                                                                         in den Details unten editiert.
                                                                                                         imageModal bleibt für „Bild hinzufügen". --}}
                                                                                                </form>
                                                                                                {{-- Metadaten hinter <details>-Toggle,
                                                                                                     Copyright/Quelle als inline
                                                                                                     source-picker mit Autocomplete +
                                                                                                     „Neu anlegen". --}}
                                                                                                <details class="metadata mt-2 rounded-md border border-ink-300/60 bg-canvas-dim/40 px-3 py-2">
                                                                                                    <summary class="cursor-pointer select-none text-caption text-chrome-on-dim focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                                                                        {{ __('metadata') }}
                                                                                                    </summary>
                                                                                                    <div class="mt-2 space-y-2 text-caption text-chrome-on-dim">
                                                                                                        <div>{{date('d.m.Y', strtotime($image->created_at))}}</div>
                                                                                                        @can('update', $project)
                                                                                                            <livewire:source-picker
                                                                                                                :model="$image"
                                                                                                                field="copyright"
                                                                                                                relation="copyrightImage"
                                                                                                                source-type="Copyright"
                                                                                                                :label="__('copyright')"
                                                                                                                :key="'image-copyright-'.$image->id" />
                                                                                                            <livewire:source-picker
                                                                                                                :model="$image"
                                                                                                                field="origin"
                                                                                                                relation="originImage"
                                                                                                                source-type="Origin"
                                                                                                                :label="__('origin')"
                                                                                                                :key="'image-origin-'.$image->id" />
                                                                                                        @else
                                                                                                            <div>Copyright: {{$image->copyrightImage?->name}}</div>
                                                                                                            <div>{{ __('origin') }}: {{$image->originImage?->name}}</div>
                                                                                                        @endcan
                                                                                                    </div>
                                                                                                </details>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endforeach
																					</div>
                                                                                </div>
                                                                            </li>
                                                                        @endif
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                            @else
                                                <ul class="list-group  ui-sortable-content sortable_list_content connectedSortableContent" data-entry="{{$entry->id}}" id="{{$entry->id}}" data-reorder-element="content" data-reorder-url="{{ route('chapter.drag') }}">
                                                   {{-- <li class="" data-content="" data-entry="{{$entry->id}}">
                                                    </li> --}}
                                                </ul>
                                            @endif
                                            <div class="mb-4">
                                                @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                                                    <span data-toggle="tooltip"
                                                          data-placement="top"
                                                          title="{{__('add_content')}}"> <button
                                                                type="button"
                                                                class="addContent btn btn-secondary add_item"
                                                                data-chapter="{{$chapter->name}}"
                                                                data-entry="{{$entry->name}}"
                                                                data-id="{{$entry->id}}"
                                                                data-toggle="modal"
                                                                data-target="#contentModal">{{__('new_element')}} <i class="bi bi-plus-circle-fill m-2"></i> </button></span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <ul class="list-group ui-sortable-entry sortable_list_entry connectedSortableEntry" id="{{$chapter->id}}" data-reorder-element="entry" data-reorder-url="{{ route('chapter.drag') }}">
                                    <li>&nbsp;</li>
                                </ul>
                            @endif
                        </div>
                    </div>
                    @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                        <div class="mb-4">
                        <span data-toggle="tooltip" data-placement="top" title="{{__('add_entry')}}"><button type="button"
                                                                                                        class="addEntry btn btn-secondary add_entry"
                                                                                                        data-chapter="{{$chapter->name}}"
                                                                                                        data-id="{{$chapter->id}}"
                                                                                                        data-toggle="modal"
                                                                                                        data-target="#entryModal">{{__('new_entry')}} <i class="bi bi-plus-circle-fill m-2"></i> </button></span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
        <a class="btn btn-secondary btn-lg add_chapter" data-toggle="modal" data-target="#myModal">

            {{__('new_chapter')}} <i class="bi bi-plus-circle-fill m-2"></i>
        </a>
    @endif
    <hr class="mt-5 mb-5">
    <!-- Modal Chapter -->

    <x-ui.modal id="myModal" :title="__('add_new_element')">
        <div class="row">
            <div id="infoMsg" class=""></div>
            <div class="writeinfo"></div>
            <div class="col-xs-12">
                <form id="chapter_frm" name="projectForm"
                      action="{{ route('chapters.store') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    {{-- Phase 2 / D.12: _method-Override für die PATCH-Variante --}}
                    <input name="_method" type="hidden" value="">
                    <div class="col-xs-6">
                        <input name="projectId" id="projectId" type="hidden" class="form-control mb-3"
                               value="{{$project->id}}">
                        <input name="chapterId" type="hidden" class="form-control mb-3"
                               value="">
                        {{__('chapter_title')}}
                        <input name="chapterTitle" id="chapterTitle" type="text"
                               class="form-control mb-3 title-change"
                               placeholder="{{__('chapter_title')}}">
                    </div>
                    <div class="col-xs-12">
                        {{__('chapter_subtitle')}}
                        <input id="chapterSubtitle" name="chapterSubtitle" type="text"
                               class="form-control mb-3" placeholder="{{__('chapter_subtitle')}}">
                    </div>
                    <div class="col-xs-12">
                        {{__('chapter_description')}}
                        <div id="chapterDescription"></div>
                    </div>
                    <div class="col-xs-12 mt-4">
                        <button id="submit_chapter" type="submit" class="btn btn-primary float-right">{{__('save')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </x-ui.modal>

    <x-ui.modal id="commentModal" :title="__('add_new_element_comment')" size="lg">
        <div class="row">
            <div id="headerComment"></div>

            <div id="listComment"></div>
            <form id="frmComment" action="" method="post">
                @csrf
                <input name="id" type="hidden" id="commentId">
                <input name="IdProjectComment" type="hidden" id="IdProjectComment">
                <div class="col-xs-12 mt-7">
                    <textarea id="commentProjectId" name="comment" class="form-control mb-3"
                              placeholder="{{__('leave_comment')}}" onkeyup="enableButton()"></textarea>
                </div>
                <div class="col-xs-12">
                    <button id="commentButton" type="submit" class="btn btn-primary float-right reply-comment" disabled>{{__('save')}}</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <x-ui.modal id="previewModal" :title="__('create_html_output')">
        <div class="row m-2">
            <div id="headerComment"></div>
            <div id="listComment"></div>
            <form id="" action="{{route('preview')}}" method="get">
                @csrf
                <input name="project" type="hidden" value="{{$project->id}}">
                <div class="form-check">
                    <input type="color" value="#EDBA0E" class="form-check-input color-element" name="colorAccent">
                    <label class="form-check-label">{{__('color_accent')}}</label>
                </div>
                <div class="form-check">
                    <input type="color" value="#EDBA0E" class="form-check-input color-element" name="colorChapter">
                    <label class="form-check-label" >{{__('color_chapter')}}</label>
                </div>
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="backgroundSecond">
                    <label class="form-check-label" >{{__('background_second')}}</label>
                </div>
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="collapse">
                    <label class="form-check-label" >{{__('collapse')}}</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="pdf">
                    <label class="form-check-label" >{{__('pdf')}}</label>
                </div>
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-primary" >{{__('html')}}</button>
                </div>
            </form>
        </div>
    </x-ui.modal>
    @include('Entry.index')
    @include('contents.index')
    {{-- Stakeholder-Fix Juni 2026: `@include('contents.gallery')` war
         hier ein Doppel-Include — `contents.index` (Z. 86) lädt das
         Gallery-Modal bereits transitiv. Folge: galleryModal + Form +
         alle Hidden Inputs (galleryId, title, …) waren DOM-doppelt,
         was den Submit unzuverlässig machte (POST /save-gallery → 404).
         Image-/Audiovisual-Modal kommen weiter direkt rein — die
         haben keine eigenen Sub-Includes. --}}
    @include('contents.image')
    @include('contents.audiovisual')
@endsection
@section('footer')
    @if(Auth::user()->can('publish', $project) || Auth::user()->can('preview'))
        <div class="footer-background p-3 my-3 border">
            <button type="button" class="m-4" data-toggle="modal" data-target="#previewModal" >{{__('pdf')}} <i class="bi bi-file-earmark-pdf-fill"></i>
            </button>
            <button type="button" class="m-4" data-toggle="modal" data-target="#previewModal" >{{__('preview')}} <i class="bi bi-globe"></i>
            </button>
		<span class="right">	<a href="https://app.crowdcurat.io/downloads/html.zip" class="m-4"  target="_blank" >{{__('download')}} <i class="bi bi-globe"></i>
            </a></span>
        </div>
    @endif
@endsection
@push('scripts')
    <script>
        $(".rotate").click(function() {
            $(this).toggleClass("down");
        })

        function activeComment(id) {
            $('#edit_' + id).show();
            $('#original_' + id).hide();
        }

        function activeReply(id) {
            $('#reply_' + id).show();
            $('#original_' + id).hide();
        }

        function cancelComment(id) {
            $('#edit_' + id).hide();
            $('#original_' + id).show();

        }

        function cancelReply(id) {
            $('#reply_' + id).hide();
            $('#original_' + id).show();

        }

        //Set status for comment
        $(document).on('change', '.status-list', function (el) {
            //var conf = confirm('Are you sure want to change status ?');

            let url;

            var statusName = $(el.target).val();
            var id = $(el.target).attr("data-id");
            var model = $(el.target).attr("data-model");

            switch (model) {
                case "chapter":
                    url = "{{ route('comment.chapter.status') }}";
                    break;
                case "entry":
                    url = "{{ route('comment.entry.status') }}";
                    break;
                case "text":
                    url = "{{ route('comment.text.status') }}";
                    break;
                case "image":
                    url = "{{ route('comment.image.status') }}";
                    break;
                case "project":
                    url = "{{ route('comment.project.status') }}";
                    break;
            }

            $.ajax({
                type: 'POST',
                url: url,
                data: {id: id, model: model, status: statusName},
                success: function (data) {
                    //console.log(data);
                }
            });

        });

        //$(document).ready(function () {
        $('#btn_text').click(function () {
            let hvalue = quill.root.innerHTML;
            $(this).append("<textarea name='contentText' style='display:none'>" + hvalue + "</textarea>");
        });

        //Submit chapter
        $('#submit_chapter').click(function () {
            let chapterDescription = quillChapter.root.innerHTML;
            $(this).append("<textarea name='chapterDescription' style='display:none'>" + chapterDescription + "</textarea>");
        });

        //Submit entry
        $('#submit_entry').click(function () {
            let entryDescription = quillEntry.root.innerHTML;
            $(this).append("<textarea name='entryDescription' style='display:none'>" + entryDescription + "</textarea>");
        });

        //tooltip initialize
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })

        function initialize() {
            quill.container.firstChild.innerHTML = '';
            $('#copyrightText').val('');
            $('#originText').val('');
            $('#textId').val('');
            $('#url').text('');
            $('#altText').val('');
            $('#copyrightImage').val('');
            $('#originImage').val('');
            $('#imageId').val('');
            $('#image').val('');
            $('#newImage').val('');
        }

        let Font = Quill.import('formats/font');
        Font.whitelist = ['times-new-roman', 'arial', 'Sans Serif'];
        Quill.register(Font, true);

        let toolbarOptions = [
            [{
                'header': [1, 2, 3, 4, 5, 6, false]
            }],
            ['bold', 'italic', 'underline', 'strike'], // toggled buttons
            [{
                'list': 'ordered'
            }, {
                'list': 'bullet'
            }],
            [{
                'indent': '-1'
            }, {
                'indent': '+1'
            }], // outdent/indent
            [{
                'direction': 'rtl'
            }], // text direction
            [{
                'size': ['small', false, 'large', 'huge']
            }], // custom dropdown

            [{
                'color': []
            }, {
                'background': []
            }], // dropdown with defaults from theme
            [{
                'font': ['', 'times-new-roman', 'arial']
            }],
            [{align: ''}, {align: 'center'}, {align: 'right'}, {align: 'justify'}],
            ['link'],
            ['clean'] // remove formatting button
        ];
        let quill = new Quill('#contentText', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        let quillEntry = new Quill('#entryDescription', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        let quillChapter = new Quill('#chapterDescription', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        /**
         * Add protocol to link if it is missing. Considers the current selection in Quill.
         */
        function updateLink() {
            var selection = quill.getSelection(),
                selectionChanged = false;
            if (selection === null) {
                var tooltip = quill.theme.tooltip;
                if (tooltip.hasOwnProperty('linkRange')) {
                    // user started to edit a link
                    lastLinkRange = tooltip.linkRange;
                    return;
                } else {
                    // user finished editing a link
                    var format = quill.getFormat(lastLinkRange),
                        link = format.link;
                    quill.setSelection(lastLinkRange.index, lastLinkRange.length, 'silent');
                    selectionChanged = true;
                }
            } else {
                var format = quill.getFormat();
                if (!format.hasOwnProperty('link')) {
                    return; // not a link after all
                }
                var link = format.link;
            }
            // add protocol if not there yet
            if (!/^https?:/.test(link)) {
                link = 'http:' + link;
                quill.format('link', link);
                // reset selection if we changed it
                if (selectionChanged) {
                    if (selection === null) {
                        quill.setSelection(selection, 0, 'silent');
                    } else {
                        quill.setSelection(selection.index, selection.length, 'silent');
                    }
                }
            }
        }


        //Add Entry
        $('.addEntry').click(function () {
            $('#entryTitle').val('');
            $('#entrySubtitle').val();
            let id = $(this).attr("data-id");
            let chapter = $(this).attr("data-chapter");
            $('input[name="chapterId"]').val(id);
            $('#lblChapter').text(chapter);
        })

        // Phase 2 / D.12: zentrale Helper für Chapter-Form-Mode.
        // Phase-1-Verzweigung im Controller ist weg — wir müssen jetzt
        // im Client zwischen POST /chapters und PATCH /chapters/{id}
        // umschalten.
        const chapterStoreUrl = "{{ route('chapters.store') }}";
        const chapterUpdateUrlTpl = "{{ route('chapters.update', ':id') }}";

        function resetChapterForm() {
            $('#chapter_frm').attr('action', chapterStoreUrl);
            $('#chapter_frm input[name="_method"]').val('');
            $('input[name="chapterId"]').val('');
        }

        function setChapterFormUpdate(id) {
            $('#chapter_frm').attr('action', chapterUpdateUrlTpl.replace(':id', id));
            $('#chapter_frm input[name="_method"]').val('PATCH');
        }

        // Chapter-Modal kann sowohl für Add (Default) als auch für
        // Modify geöffnet werden; ein hidden-Reset stellt sicher, dass
        // der nächste Add nicht aus dem letzten Update-Mode lebt.
        $('#myModal').on('hidden.bs.modal', resetChapterForm);

        // Modify-Chapter-Handler entfällt seit Phase 5c.6.a — Kapitel-
        // Titel, Subtitel und Beschreibung werden direkt im
        // Kapitel-Card editiert, das Chapter-Edit-Modal ist raus.
        // Add-Chapter-Modal (myModal) bleibt unangetastet,
        // resetChapterForm/setChapterFormUpdate werden dort weiter
        // genutzt.

        //Modify entry
        // Phase 2 / D.13: zentrale Helper für Entry-Form-Mode (analog
        // zu Chapter, siehe oben). Add ist Default, Modify schaltet auf
        // PATCH um.
        const entryStoreUrl = "{{ route('entries.store') }}";
        const entryUpdateUrlTpl = "{{ route('entries.update', ':id') }}";

        function resetEntryForm() {
            $('#entry_frm').attr('action', entryStoreUrl);
            $('#entry_frm input[name="_method"]').val('');
            $('input[name="entryId"]').val('');
        }

        function setEntryFormUpdate(id) {
            $('#entry_frm').attr('action', entryUpdateUrlTpl.replace(':id', id));
            $('#entry_frm input[name="_method"]').val('PATCH');
        }

        $('#entryModal').on('hidden.bs.modal', resetEntryForm);

        // Modify-Entry-Handler entfällt seit Phase 5c.6.b — Entry-
        // Titel, Subtitel und Beschreibung werden direkt im Entry-
        // Card editiert. Add-Entry-Modal bleibt unangetastet.

        // Modify-Text-Handler entfaellt seit Phase 5c.6.c.4 —
        // Text-Content wird ueber die rich-text-editor-Volt-
        // Komponente direkt im Content-Card editiert. Der
        // contentModal + der Quill (quill) bleiben fuer den
        // Add-Text-Fall bestehen (neuer Textblock hinzufuegen).

        // Modify-Image-Handler entfaellt seit Phase 5c.6.c.4-Followup —
        // Copyright/Quelle werden ueber die source-picker-Volt-Komponente
        // in den Bild-Details editiert, das Alt-Feld ueber inline-editor.
        // imageModal bleibt bestehen fuer den Add-Image-Fall.

        // Modify-Gallery-Handler entfaellt seit Phase 5c.6.c.1 —
        // Title, Subtitle und Description werden direkt im Gallery-
        // Card via der Volt-Komponente inline-editor editiert.
        // Add-Image-Modal (imageModal) bleibt fuer neue Bilder in
        // der Galerie.

        //Add Content
        $('.addContent').click(function () {
            let id = $(this).attr("data-id");
            let chapter = $(this).attr("data-chapter");
            let entry = $(this).attr("data-entry");
            $('input[name="entryId"]').val(id);
            $('#contentType').show();
            $('#addText').hide();
            $('#addImage').hide();
            $('#chapterLbl').text(chapter);
            $('#entryLbl').text(entry);
            initialize();
        })

        //Add Content
        $('.addImage').click(function () {
            let id = $(this).attr("data-id");
            let chapter = $(this).attr("data-chapter");
            let entry = $(this).attr("data-entry");
            let entryId = $(this).attr("data-entryId");
            let gallery = $(this).attr("data-gallery");
            $('input[name="galleryId"]').val(id);
            // Hotfix-Welle 0 (2026-06-23): entryId-Hidden-Input
            // setzen, sonst läuft saveImage auf Entry::findOrFail(0)
            // → 404 und Bild wird zwar gespeichert (über galleryId),
            // aber die Authorize-Kette schlägt fehl und die
            // Render-Seite zeigt das Bild nicht.
            $('input[name="entryId"]').val(entryId);
            $('#chapterLbl').text(chapter);
            $('#entryLbl').text(entry);
            $('#galleryLbl').text(gallery);
            initialize();
        })

        //Toggle Text Block
        $('.add-Text').click(function (e) {
            e.preventDefault();
            $('#addText').toggle();
            $('#contentType').toggle();
            $('#addImage').hide();
        })

        //Toggle Image Block
        $('.add-Image').click(function (e) {
            $('#galleryModal').modal('show');
            e.preventDefault();
        })

        //Toggle video Block
        $('.add-video').click(function (e) {
            resetValues();
            $('#savedAudio').hide();
            $('#link').show();
            $('#type').val('video');
            $('#audiovisualModal').modal('show');
            e.preventDefault();
        })

        //Toggle video Block
        $('.add-audio').click(function (e) {
            resetValues();
            $('#savedAudio').show();
            $('#link').hide();
            $('#type').val('audio');
            $('#audiovisualModal').modal('show');
            e.preventDefault();
        })

        // Autocomplete-Felder. In $(document).ready(...) gewrappt, weil
        // der jQuery-Shim fuer typeahead (resources/js/typeahead.js) als
        // Vite-Module-Script erst nach DOMContentLoaded verfuegbar ist;
        // ohne den Wrapper liefe der Aufruf direkt beim HTML-Parsing.
        var path = "{{ route('autocomplete') }}";
        $(function () {
            $('#copyrightText').typeahead({
                source: function (query, process) {
                    return $.get(path, {query: query, type: 'Copyright'}, function (data) {
                        return process(data);
                    });
                },
                displayText: function (item) {
                    return `${item}`;
                },
                afterSelect: function (item) {
                    $('#copyrightText').val(item);
                },
                fitToElement: true
            });

            $('#originText').typeahead({
                source: function (query, process) {
                    return $.get(path, {query: query, type: 'Origin'}, function (data) {
                        return process(data);
                    });
                },
                displayText: function (item) {
                    return `${Object.values(item)}`;
                },
                afterSelect: function (item) {
                    $('#originText').val(Object.values(item));
                },
                fitToElement: true
            });

            $('#copyrightImage').typeahead({
                source: function (query, process) {
                    return $.get(path, {query: query, type: 'Copyright'}, function (data) {
                        return process(data);
                    });
                },
                displayText: function (item) {
                    return `${Object.values(item)}`;
                },
                afterSelect: function (item) {
                    $('#copyrightImage').val(Object.values(item));
                },
                fitToElement: true
            });

            $('#originImage').typeahead({
                source: function (query, process) {
                    return $.get(path, {query: query, type: 'Origin'}, function (data) {
                        return process(data);
                    });
                },
                displayText: function (item) {
                    return `${Object.values(item.name)}`;
                },
                afterSelect: function (item) {
                    $('#originImage').val(Object.values(item.name));
                },
                fitToElement: true
            });
        });

        //Add thumbnail
        $(document).on('change', '.btn-file :file', function () {
            let input = $(this),
                label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
            input.trigger('fileselect', [label]);
        });

        $('.btn-file :file').on('fileselect', function (event, label) {

            let input = $(this).parents('.input-group').find(':text'),
                log = label;

            if (input.length) {
                input.val(log);
            } else {

            }

        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();

                reader.onload = function (e) {
                    $('#img-upload').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#imgInp").change(function () {
            readURL(this);
        });

        $("#upload").change(function () {
            readURL(this);
        });

        function confirmExit() {
            if (formmodified == 1) {
                return "Exit?";
            }
        }

        //Add Chapter through Ajax
        $.ajaxSetup({headers: {'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')}});

        $("#addNewChapter").click(function (e) {
            e.preventDefault();

            let chapterTitle = $("input[name=chapterTitle]").val();
            let chapterSubtitle = $("input[name=chapterSubtitle]").val();
            let chapterDescription = $("textarea[name=chapterDescription]").val();
            let projectId = {!! json_encode($project->id) !!};

            $.ajax({
                type: 'POST',
                url: "{{ route('chapters.store') }}",
                data: {
                    chapterTitle: chapterTitle,
                    chapterSubtitle: chapterSubtitle,
                    chapterDescription: chapterDescription,
                    projectId: projectId
                },
                success: function (data) {
                    //alert(data.data['id']);
                    $('#infoMsg').addClass('alert alert-success');
                    $('#infoMsg').html(data.success);

                }
            });
        });

        //})

        $('#userRole').change(function () {
            let userId = $('#userRole').val();
            let project = $(this).attr('data-project');
            getUserRights(userId, project);
        })

        $('.edit-user').click(function () {
            let id = $(this).attr('data-id');
            let project = $(this).attr('data-project');
            $("div.user-permission select").val(id).change();
            getUserRights(id, project);
        })

        function getUserRights(id, project) {
            $(".cb-element input:checkbox").prop("checked", false);
            let url = "{{ route('permission.project', ":id") }}";
            id = id + '_' + project;
            url = url.replace(':id', id);

            $.ajax({
                type: 'GET',
                url: url,
                success: function (data) {
                    if (data.length > 0) {
                        $("#list").find('[value=' + data.join('], [value=') + ']').prop("checked", true);
                    }
                    console.log(data);
                }
            });
        }

        //Logs
        $(".reset-version").click(function () {
            $('#oldActivity').html('');
            $('#currentActivity').html('');
            $('#valueReset').val('');
            $('#idReset').val('');
            $('#infoLog').html('');
            let id = $(this).attr('data-id');
            let userName = $(this).attr('data-user');
            let timestamp = $(this).attr('data-date');
            let oldActivity = $(this).attr('data-preview');
            let newActivity = $(this).attr('data-new');
            let oldValue = $(this).attr('data-old');
            let chapter = $(this).attr('data-chapterName');
            let entry = $(this).attr('data-entryName');

            $('#valueReset').val(oldValue);
            $('#idReset').val(id);
            $(oldActivity).appendTo('#oldActivity');
            $(newActivity).appendTo('#currentActivity');
            $('<p> Chapter: ' + chapter + '</p>').appendTo('#infoLog');
            $('<p> Entry: ' + entry + '</p>').appendTo('#infoLog');

        })

        //Onchange update project
        $('.update-project').on('focusin', function () {
            $(this).data('val', $(this).val());
        });

        $('.update-project').on('change', function () {
            var prev = $(this).data('val');
            var current = $(this).val();
            if (prev !== current) {
                $('#updateProjectBtn').html('<button id="btn_save" class="btn btn-secondary btn-block text-left" type="submit" name="btn_submit" value="Save"><i class="bi bi-file-earmark m-2"></i>{{__('save')}}</button>');
            }

        });

        $('.enable-textarea').keyup(function(e) {
            let input = e.target.id;
            let dInput = this.value;
            if (this.value === '') {
                $('#commentProjectId_'+e.target.id).prop('disabled', true);
            }else {
                $('#commentProjectId_'+e.target.id).prop('disabled', false);
            }

        });


        function enableButton(){
            $('#commentButton').prop('disabled', false);
        }



        $(document).ready(function () {

			/*$(".col-sm-3").append($(".row.versions"));*/

            //Set project comment id
            $('#IdProjectComment').val({!! json_encode($project->id) !!});

            //Check copyright and origin for Text
            $("#text_frm").submit(function (event) {
                let copyrightText = $("input[name='copyrightText']", this).val();
                let originText = $("input[name='originText']", this).val();

                if (originText.length === 0) {
                    event.preventDefault();
                    alert('Origin should not be empty');
                }

                if (copyrightText.length === 0) {
                    event.preventDefault();
                    alert('Copyright should not be empty');
                }
            });

            //Check copyright and origin for image
            $("#image_frm").submit(function (event) {
                let copyrightImage = $("input[name='copyrightImage']", this).val();
                let originImage = $("input[name='originImage']", this).val();

                if (originImage.length === 0) {
                    event.preventDefault();
                    alert('Origin should not be empty');
                }

                if (copyrightImage.length === 0) {
                    event.preventDefault();
                    alert('Copyright should not be empty');
                }
            });

        });

        //collapse and expand chapter
        function collapseExpand(id){

            if($('#chapter_'+id).is(':animated') ) {

                return false;

            }

            $('#chapter_'+id).slideToggle('slow');
            $('#chp_'+id).toggleClass('bi-caret-down-fill').toggleClass('bi-caret-right-fill');

        }

        //collapse entry
        function collapseExpandEntry(id){

            if($('#entry_'+id).is(':animated') ) {
                return false;
            }

            $('#entry_'+id).slideToggle('slow');
            $('#ent_'+id).toggleClass('bi-caret-right-fill').toggleClass('bi-caret-down-fill');
        }

        //Invitation for existing user
        @if(!empty(Session::get('error_code')) && Session::get('error_code') == 6)
        $('#newUserInvitation').modal('show');
        @endif

        //User not existing
        @if(!empty(Session::get('error_code')) && Session::get('error_code') == 7)
        $('#newUser').modal('show');
        @endif

        $('.edit-user').click(function (event) {
            //event.preventDefault();
            $("#selectedUser").html('');
            let user = $(this).attr("data-id");
            let project = $(this).attr("data-project");
            let permission = $(this).attr("data-permission");
            let listPermissions = @json($permissions);
            jQuery.each(listPermissions, function (i, val) {
                let check = "";
                if (val.id in $.parseJSON(permission)) check = "checked";
                $("#selectedUser").append('<div class="form-check"><input name="permissions[]" class="form-check-input" type="checkbox" value="' + val.id + '" id="flexCheckChecked"' + check + ' > <label class="form-check-label" for="flexCheckChecked"> ' + val.name + ' </label></div>')
            });
            $('#selectedUserId').val(user);
            $('#editUserPermission').load('/user/' + user + '/project/' + project + '/info');
        })

        $('.add-user').click(function () {
            $("#detailUser").html('<form action="{{route('check.email')}}" method="POST" enctype="multipart/form-data" class="form-group form-inline" id="frmCheckEmail">@csrf<input name="project" @isset($project->id) value="{{$project->id}}" @endisset type="hidden"/><div class="form-group col-xs-8 mb-2"><input type="email" class="form-control" name="userEmail" placeholder="User email" style="width: 100% !important;"></div><button id="userEmailCheck" type="submit" class="btn btn-primary mb-2">Einladen</button></form>');
        })

        $('.comment-edit').click(function (){

        })


        // Inline-Edit der Kommentar-Texte laeuft jetzt ueber die
        // Volt-Komponente comment-text-editor in projects/description.
        // x-editable und seine Form-Buttons fallen damit.


        // Update Status: Comment-Status laeuft jetzt ueber die Volt-Komponente
        // (comment-status-switcher) in projects/description.blade.php.
        // Der fruehere $.ajax-POST-Pfad auf /comment/{id}/update/{status}
        // wird nicht mehr aus dem Frontend gerufen — die Route bleibt
        // vorerst stehen (Route-Cleanup folgt spaeter).

        $(document).ready(function (){
            $('.reply').hide();
        })

        $('.enable-reply').click(function (){
            $('.reply_'+this.id).toggle();
        })

        // Audiovisual-Modify-Handler entfaellt seit Phase 5c.6.c.3 —
        // link/type/copyright/source werden ueber die inline-editor-
        // Volt-Komponente direkt unter dem Player editiert. Die
        // resetValues()-Hilfsfunktion wird vom Add-Handler weiter
        // unten noch benoetigt.

        function resetValues(){
            $('#link').val('');
            $('#copyright').val('');
            $('#source').val('');
            $('#type').val('');
        }

        //Drag and drop
        // Reader-Frontend-Härtung Juni 2026: jQuery-Sortable-Inits
        // nur für User mit update-Recht. Backend (chapter.drag →
        // ChapterController::saveDragAndDrop) blockt seit Phase 4
        // via $this->authorize('update', $project); das Frontend
        // initialisierte die Sortables aber für alle User —
        // Reader konnten Content visuell verschieben, der POST
        // gegen die Route lief in 403, der UI-Zustand blieb
        // verschoben bis zum nächsten Refresh. Verwirrend, UX-
        // mäßig falsch und in der Konsole ein 403-Spuk.
        @can('update', $project)
        $(function() {
            $( ".sortable_list_chapter" ).sortable({
				placeholder:"placeholder",
                connectWith: ".connectedSortableChapter",

                update: function(event, ui) {
                    let data = {};
                    data['data'] = $(this).sortable('toArray', {attribute:'data-chapter'});
                    data['element'] = 'chapter';
                    data['project'] = {{$project->id}};

                    $.ajax({
                        data: {"data":data},
                        type: 'POST',
                        url: "{{route('chapter.drag')}}",
                        beforeSend: function() {
                            // setting a timeout
                            //console.log(JSON.stringify(data))
                        },
                        success: function (data) {
                            //location.reload();
                        },
                        error: function () {

                        }
                    })
                }
            });

            $( ".sortable_list_entry" ).sortable({
				placeholder:"placeholder",
                connectWith: ".connectedSortableEntry",
                update: function(event, ui) {
                    let data = {};
                    data['data'] = $(this).sortable('toArray', {attribute:'data-entry'});
                    data['element'] = 'entry';
                    data['chapter'] = this.id;

                    $.ajax({
                        data: {"data":data},
                        type: 'POST',
                        url: "{{route('chapter.drag')}}",
                        beforeSend: function() {

                        },
                        success: function (data) {

                        },
                        error: function () {

                        }
                    })
                }
            });

            $( ".sortable_list_content" ).sortable({
				placeholder:"placeholder",
                connectWith: ".connectedSortableContent",
                update: function(event, ui) {
                    let data = {};
                    data['data'] = $(this).sortable('toArray', {attribute:'data-content'});
                    data['element'] = 'content';
                    data['entry'] = this.id;

                    $.ajax({
                        data: {"data":data},
                        type: 'POST',
                        url: "{{route('chapter.drag')}}",
                        beforeSend: function() {

                        },
                        success: function (data) {

                        },
                        error: function () {

                        }
                    })
                }
            });


        });
        @endcan {{-- Reader-Frontend-Härtung Juni 2026 (Sortable-Init-Gate) --}}

    </script>
@endpush
