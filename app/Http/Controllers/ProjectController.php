<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

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

If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use App\Data\ProjectData;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Entry;
use App\Models\Image;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Text;
use App\Models\User;
use App\Services\CommentRetrieve;
use App\Services\LogService;
use App\Services\ProjectImageService;
use App\Services\ProjectPermissionService;
use App\Services\RevisionRevertService;
use App\Services\UserService;
use App\Support\ProjectLegalText;
use App\Support\RoleName;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class ProjectController extends Controller
{
    /**
     * Instantiate a new ProjectController instance.
     */
    public function __construct(
        private readonly ProjectImageService $images,
        private readonly ProjectPermissionService $permissions,
        // I3 (2026-08-21): Services die frueher als `new CommentRetrieve;`
        // bzw. `new UserService;` in einzelnen Actions gebaut wurden,
        // laufen jetzt ueber den Container.
        // Q4-Etappe 2 / I6 (2026-08-27): `CommentService` wandert mit den
        // vier Kommentar-Methoden in den ProjectCommentController;
        // `CommentRetrieve` bleibt hier, weil `edit()` es fuer das
        // eingebettete Kommentar-Panel nutzt.
        private readonly CommentRetrieve $commentRetrieve,
        private readonly UserService $users,
        // Q3-Abschluss (2026-08-27): Verlauf-Wiederherstellen aus dem
        // Fat-Controller extrahiert (~78 LoC).
        private readonly RevisionRevertService $revert,
    ) {
        $this->middleware('auth');
        // Block D / D.4: Drei-Wege-Authorization in einen Pfad
        // konsolidiert (ADR-0005). Vorher liefen hier zusätzlich
        // `permission:add` (für create/store), `permission:view`
        // (für index) und `permission:comment` (für commentProject/
        // getProjectComment) parallel zu FormRequest-`authorize()`
        // und inline `$this->authorize(...)`. Authorization läuft
        // jetzt durchgehend über die ProjectPolicy — der jeweilige
        // Action-Body ruft `$this->authorize(...)` oder die
        // FormRequest-`authorize()`-Methode tut es.
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $this->authorize('viewAny', Project::class);

        // get all available projects
        $data = $this->getAllProjects();

        return view('projects.index', compact('data'));
    }

    /**
     * Return list of all active projects.
     *
     * Block D PR 2 / D.5: delegiert an `ProjectPermissionService::
     * listProjectsForUser`. Vor PR 2 stand hier die Query inline,
     * mit Admin-Pfad via `users.isAdmin()` und Nicht-Admin-Pfad
     * über `invitations.guest_id`. Service nutzt jetzt
     * `project_user_permissions` als Quelle der Wahrheit für die
     * Eingeladenen-Sicht (siehe Service-Doku).
     */
    public function getAllProjects(): EloquentCollection
    {
        return $this->permissions->listProjectsForUser(Auth::user());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * F-SEC-010: user_id ist nicht in Project::$fillable. Wir fillen
     * den Mass-Assignment-Block über das DTO und setzen user_id
     * anschließend explizit aus Auth::user()->id — ein Request kann
     * keine fremde user_id injizieren. Der Status-Default kommt aus
     * der App-Config, nicht aus dem Request.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $logo = $this->images->store($request->file('project_image'));
        $data = ProjectData::fromRequest($request, $logo);

        $project = new Project;
        $project->fill(array_merge(
            ['status' => config('project.status.default')],
            $data->toArray(),
        ));
        $project->user_id = Auth::user()->id;
        $project->save();

        return redirect()->route('chapters.index', ['id' => $project->id])
            ->with('success', 'Project added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Project $project)
    {
        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // Reader-via-URL-Smoke gefunden — show öffnete fremde
        // Projects ohne Gate.
        $this->authorize('view', $project);

        return view('projects.show', compact('project'));
    }

    /**
     * Phase 5d.4: Berechtigungssicht (Screen 3B). Ersetzt die alte
     * Modal-Kaskade aus projects/create. View delegiert an die
     * Livewire-Volt-Komponente resources/views/livewire/
     * project-permissions.blade.php.
     */
    public function permissions(Project $project)
    {
        $this->authorize('invite', $project);

        return view('projects.permissions', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Request $request, Project $project)
    {
        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // Reader-via-URL-Smoke (2026-06-21) zeigte, dass /projects/{id}/edit
        // jeden eingeloggten User in fremde Projekte hineinblicken liess.
        // view-Gate reicht: eingeladene Reader sehen die Edit-Maske mit
        // ihren Lese-Rechten, Fremde bekommen 403.
        $this->authorize('view', $project);

        $textLog = [];
        $comments = [];
        $isComment = false;

        if (isset($request['comment'])) {
            $isComment = true;
            $comment = $this->commentRetrieve;

            $comments = $comment->getComments($request['model'], $request['comment']);

        }

        if (isset($request['log']) && isset($request['model'])) {

            $textLog = $this->history($request['model'], $request['log']);
        }

        $permissions = Permission::all();
        // F-DB-013: vorher Role::where('id', 'not like', '1').
        $listRole = Role::where('name', '!=', RoleName::ADMIN->value)->pluck('name', 'id');
        // F-DB-014: SoftDeletes-Scope greift implizit — kein whereNull nötig.
        $users = User::all();
        $userService = $this->users;
        $listPermissions = $userService->getAllUsers($project->id);
        $allPermissions = Permission::pluck('name', 'id');
        $currentUserPermissions = $this->permissions->getCurrentUsersPermissions(Auth::user()->id);

        // withEditTree() lädt die volle Hierarchie für die in
        // projects/edit eingeschlossene View chapters/index eager.
        $data = Project::withEditTree()->findOrFail($project->id);
        $listGrantedUsers = $this->permissions->getUsersForThisProject($project->id);

        $links = session()->has('links') ? session('links') : [];
        $currentLink = request()->path();
        array_unshift($links, $currentLink);
        session(['links' => $links]);

        return view(
            'projects.edit',
            compact(
                'project',
                'data',
                'permissions',
                'users',
                'listPermissions',
                'listGrantedUsers',
                'textLog',
                'allPermissions',
                'currentUserPermissions',
                'listRole',
                'comments',
                'isComment'
            )
        );
    }

    /**
     * Helper für edit(): liefert die Activity-Log-Liste für ein
     * konkretes Content-Modell innerhalb eines Project-Edit-Pfades.
     *
     * Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
     * Sichtbarkeit auf `private` reduziert. Vorher `public`, aber
     * nicht via Route erreichbar — der einzige Aufrufer ist
     * `edit()` (Z. 198), das selbst gegated ist. Damit ist der
     * Pfad indirekt geschützt; ein eigener `authorize`-Call wäre
     * redundant.
     *
     * @return array<int, array{id: int|string, userName: string, created_at: mixed}>
     */
    private function history($model, $id)
    {
        $type = "App\Models\\".$model;
        $exception = '[]';

        // Strict-Mode: $value->causer wird in der Schleife für jedes
        // Activity-Item gelesen — ohne Eager-Load wirft Laravel 11+
        // mit preventLazyLoading() eine LazyLoadingViolationException
        // (Karl-Befund 2026-06-21). E.7b 4a-Hotfix-II.a-Followup.
        $activities = Activity::with('causer')
            ->where('subject_id', '=', $id)
            ->where('subject_type', '=', $type)->where('description', 'NOT LIKE', '%created%')
            ->where('properties', 'NOT LIKE', '%is_translate%')
            ->where('properties', 'NOT LIKE', '%'.$exception.'%')
            ->where('properties->language', Lang::getLocale())
            ->orderBy(
                'updated_at',
                'desc'
            )->get();

        $logs = [];

        foreach ($activities as $key => $value) {
            if ($value->changes->isNotEmpty()) {
                $firstName = isset($value->causer->name) ? $value->causer->name : null;
                $lastName = isset($value->causer->last_name) ? $value->causer->last_name : null;
                $logs[] = [
                    'id' => $value->id,
                    'userName' => $firstName.' '.$lastName,
                    'created_at' => isset($value->created_at) ? $value->created_at : null,
                ];
            }
        }

        return $logs;
    }

    /**
     * Update the specified resource in storage.
     *
     * NF-SEC-007: Logo-Filename kommt ausschließlich aus dem
     * ProjectImageService, nie aus dem Request-`logo`-Feld.
     * UpdateProjectRequest hat den File vorher MIME-validiert.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $logo = $this->images->store($request->file('project_image'));
        $data = ProjectData::fromRequest($request, $logo);

        // terms/description sind nullable und MÜSSEN als null
        // durchschlagen, wenn das Frontend sie leer schickt —
        // daher hier nicht über $data->toArray() (das filtert
        // null), sondern explizit auflisten.
        $project->update([
            'name' => $data->name,
            'imprint' => $data->imprint,
            'terms' => $data->terms,
            'description' => $data->description,
        ]);

        if ($data->logo !== null) {
            $project->update(['logo' => $data->logo]);
        }

        // Q4-Etappe 3 / C0b Fix (2026-09-07): Zitier-Settings kommen
        // nur aus dem Edit-Screen (nicht bei Create), deshalb nur
        // updaten wenn im Request tatsächlich mitgeschickt.
        if ($data->citationDepth !== null) {
            $project->update([
                'citation_depth' => $data->citationDepth,
                'source_required' => $data->sourceRequired ?? true,
            ]);
        }

        // Q4-Etappe 5 / G1 (2026-09-08): Reader-Layout.
        if ($data->readerLayout !== null) {
            $project->update(['reader_layout' => $data->readerLayout]);
        }

        return redirect()->back()->with('success', __('message_edit_project_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', __('message_delete_project_success'));
    }

    /**
     * Drag and drop
     *
     * @return Application|Factory|View
     */
    public function move()
    {
        $data = $this->getAllProjects();

        return view('projects.move', compact('data'));
    }

    // Q4-Etappe 4 / C1d (2026-09-08): `element()` und die zugehörige
    // View `projects/element.blade.php` waren ein verwaister Chapter/
    // Entry-Anlege-Screen aus der Bootstrap-3-Zeit — nirgends verlinkt,
    // kein Save-Backend. Mit dem C1-Aufräumen entfernt.

    // Q4-Etappe 2 / I6 (2026-08-27): Kommentar-Endpunkte
    // (`commentProject`, `getProjectComment`, `saveCommentProject`,
    // `setCommentStatusProject`) leben ab jetzt in
    // `App\Http\Controllers\ProjectCommentController`. Rechte-Endpunkte
    // (`setPermissionForUserOnProject`, `givePermissionToUser`,
    // `checkEmail`, `deleteUserFromProject`) in
    // `App\Http\Controllers\ProjectPermissionController`.

    /**
     * @return array|mixed
     */
    public function getCurrentLog($id)
    {
        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // Route /log/text/{id} ist text-bezogen (Name `log.text`).
        // $id ist eine Text-ID — via Text::project() navigieren wir
        // zum Project und gaten gegen view. Vorher kein Gate.
        $text = Text::findOrFail($id);
        $project = $text->project();
        if ($project === null) {
            abort(404);
        }
        $this->authorize('view', $project);

        $log = new LogService('text');
        $activities = $log->textLog($id);

        return redirect()->back()->with('activities', $activities);
    }

    /**
     * Get parent text
     *
     * @return Collection
     */
    public function getParentText($table, $model, $id)
    {
        // Security-Sweep-III (2026-06-22): SQLi-Surface über die
        // String-Parameter $table und $model geschlossen. Whitelist
        // erlaubt nur die drei vom Frontend tatsächlich genutzten
        // Kombinationen.
        $allowedTables = ['entries', 'images', 'texts'];
        $allowedModels = [
            Entry::class,
            Text::class,
            Image::class,
        ];
        if (! in_array($table, $allowedTables, true)
            || ! in_array($model, $allowedModels, true)
        ) {
            abort(404);
        }

        switch ($table) {
            case 'entries':
                return DB::table($table)
                    ->join('chapters', 'chapters.id', '=', 'entries.chapter_id')
                    ->select('chapters.name as chapter_name', 'entries.name as entry_name')
                    ->where($table.'.id', '=', $id)
                    ->get();
            case 'images':
            case 'texts':
                // E.7b Welle 4b (ADR-0022): join geht jetzt auf die
                // neuen Spalten content_id / parent_id / content_type.
                // Doppelschreibung in den Services sichert Gleichwertig-
                // keit zu den alten Spalten bis Welle 4d.
                return DB::table($table)
                    ->join('media_content', $table.'.id', '=', 'media_content.content_id')
                    ->join('entries', 'entries.id', '=', 'media_content.parent_id')
                    ->join('chapters', 'chapters.id', '=', 'entries.chapter_id')
                    ->select('chapters.name as chapter_name', 'entries.name as entry_name')
                    ->where($table.'.id', '=', $id)
                    ->where('media_content.content_type', '=', $model)
                    ->get();
        }
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function resetValue(Request $request)
    {
        // Security-Sweep-III (2026-06-22): Whitelist gegen RCE-nahen
        // Vektor via freies `::findOrFail()`. Whitelist + Feld-Zuweisung
        // wohnen seit Q3-Abschluss (2026-08-27) im RevisionRevertService;
        // der Controller macht nur noch Request-Gate + Authorize + Delegate.
        if (! $request->filled('subjectType')) {
            return redirect(session('links')[2]);
        }

        $subjectType = (string) $request['subjectType'];
        if (! $this->revert->isRevertible($subjectType)) {
            abort(403);
        }

        $model = $subjectType::findOrFail($request['subjectId']);
        $this->authorize('update', $model);

        $this->revert->revert($model, $request->all());

        return redirect(session('links')[2]);
    }

    // Q4-Etappe 2 / I6 (2026-08-27): `translateCurrentProject`,
    // `saveTranslations` und die private `allData()` leben ab jetzt in
    // `App\Http\Controllers\ProjectTranslationController`. Die
    // Sync-Warn-Logik wurde als `TranslationOutdatedMapService`
    // extrahiert.

    /**
     * Check whether input email exists
     * code_error 6: already exist
     * code_error 7: doesn't exist
     *
     * @return RedirectResponse
     */
    // Q4-Etappe 2 / I6 (2026-08-27): `checkEmail` und
    // `deleteUserFromProject` leben ab jetzt in
    // `App\Http\Controllers\ProjectPermissionController`.
    // `deleteUserFromProject` bekam beim Umzug ein `authorize('update')`-
    // Gate — siehe .werkbank/REVIEW/Q3-abschluss/2026-08-27-security-nachtrag.md.

    /**
     * Edit metadata
     *
     * @return Application|Factory|View
     */
    public function editMetaData($projectId, UserService $userService)
    {
        $project = Project::findOrFail($projectId);

        // Block E / Welle E.7a-Hotfix: vorher nur `auth`-Middleware,
        // jeder Reader konnte fremde Project-Metadaten und die
        // Permissions-Verwaltung sehen. Jetzt geht der Pfad durch
        // ProjectPolicy::update — Owner ODER Admin ODER
        // Eingeladener mit edit-Permission.
        $this->authorize('update', $project);

        $listGrantedUsers = $this->permissions->getUsersForThisProject((int) $projectId);
        // F-DB-013: vorher Role::where('id', 'not like', '1').
        $listRole = Role::where('name', '!=', RoleName::ADMIN->value)->pluck('name', 'id');
        $permissions = Permission::all();
        // Hotfix: `$listPermissions` wurde von der `projects.create`-
        // View erwartet (Zeile 168: `in_array('invite', $listPermissions)`),
        // aber nicht übergeben — bei Admin griff der Short-Circuit
        // `Auth::user()->isAdmin()` vorher, daher fiel der Bug lange
        // nicht auf. Mit dem Owner-Pfad oder einem Eingeladenen mit
        // edit-Permission läuft die View bis zur in_array-Prüfung.
        $listPermissions = $userService->getAllUsers($project->id);
        asort($listGrantedUsers);

        return \view('projects.create', compact('project', 'listGrantedUsers', 'listRole', 'permissions', 'listPermissions'));
    }

    /**
     * Preview project
     *
     * @param  $id
     * @return Application|Factory|View
     */
    // Q4-Etappe 2 / I6 (2026-08-27): `previewProject` und
    // `downloadPreview` leben ab jetzt in
    // `App\Http\Controllers\ProjectPreviewController`.

    /**
     * Phase 5aa.2/Design v6 § 3: „Systemtext übernehmen".
     *
     * Kopiert einmalig den aktuellen Systemtext (Impressum oder AGB aus
     * /settings) ins Projekt-Feld. Danach ist das Projekt entkoppelt —
     * spätere Änderungen am Systemtext greifen nicht mehr. Der leere
     * Fallback bleibt davon unberührt: bleibt das Projekt-Feld später
     * wieder leer, greift der Systemtext beim Publish automatisch
     * (siehe `ProjectLegalText::imprintFor/termsFor`).
     */
    public function adoptSystemLegalText(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $field = $request->input('field');
        if (! in_array($field, ['imprint', 'terms'], true)) {
            abort(422, 'Unknown field.');
        }

        $systemText = $field === 'imprint'
            ? ProjectLegalText::systemImprint()
            : ProjectLegalText::systemTerms();

        $project->{$field} = $systemText;
        $project->save();

        return redirect()->back()->with('success', __('message_edit_project_success'));
    }

    public function projectMetadata(Request $request)
    {

        $parameters = $request['parameters'];

        if (isset($parameters['id'])) {
            $project = Project::withCopyrightTree()->findOrFail($parameters['id']);

            // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
            // projectMetadata liefert Impressum/AGB/Quellen-Listen
            // fremder Projekte ohne Gate.
            $this->authorize('view', $project);

            if ($request->type == 'copyright') {
                // 5aa.2 Design v6 § 3: leeres Projekt-Feld → Systemtext greift.
                $content = ProjectLegalText::termsFor($project);
                $type = 'copyright';
            } else {
                $content = ProjectLegalText::imprintFor($project);
                $type = 'policy';
            }
        }

        return \view('preview.copyright', compact('project', 'parameters', 'content', 'type'));
    }
}
