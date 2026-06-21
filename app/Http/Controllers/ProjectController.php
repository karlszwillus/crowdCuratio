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
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Audiovisual;
use App\Models\Gallery;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Source;
use App\Models\Text;
use App\Models\User;
use App\Services\CommentRetrieve;
use App\Services\CommentService;
use App\Services\LogService;
use App\Services\ProjectImageService;
use App\Services\ProjectPermissionService;
use App\Services\SourceService;
use App\Services\UserService;
use App\Support\RoleName;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
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
        private readonly CommentService $comments,
        private readonly SourceService $sources,
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
            $comment = new CommentRetrieve;

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
        $userService = new UserService;
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

    /**
     * create the specified resource from storage.
     *
     * @return Response
     */
    public function element()
    {
        return view('projects.element');
    }

    /**
     * Comment project — neuer Top-Level-Kommentar.
     *
     * Route hat kein {project} in der URL, deshalb resolved Laravel
     * das Project-Argument nicht — wir laden es explizit aus
     * $request->id, wie der alte CommentTrait das auch tat.
     */
    public function commentProject(StoreCommentRequest $request): RedirectResponse
    {
        $project = Project::findOrFail($request->validated('id'));
        $this->authorize('comment', $project);
        $this->comments->addComment($project, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }

    /**
     * Retrieve all comment of current project
     *
     * @return JsonResponse
     */
    public function getProjectComment($id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('comment', $project);

        $comment = new CommentRetrieve;

        return $comment->getComments('App\Models\Project', $id);
    }

    /**
     * Routet eine save-Submission (Edit/Delete/Reply).
     */
    public function saveCommentProject(Request $request, Project $project): RedirectResponse
    {
        $this->comments->dispatchSaveAction($project, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Setzt den Status eines Comments auf einem Project.
     */
    public function setCommentStatusProject(Request $request, Project $project): JsonResponse
    {
        $this->comments->setCommentStatus((int) $request['id'], (int) $request['status']);

        return response()->json(['success' => true]);
    }

    /**
     * Set permission for user on project
     */
    public function setPermissionForUserOnProject(Request $request): RedirectResponse
    {
        $userId = (int) $request['user'];
        $projectId = (int) $request['project'];
        $permissionIds = (array) ($request['permissions'] ?? []);

        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // KRITISCH — bisher konnte JEDER eingeloggte User via direktem
        // POST `/project/permission` einem beliebigen User volle Rechte
        // auf jedes Projekt vergeben. Privilege Escalation, vergleichbar
        // mit NF-SEC-202. update-Gate: nur Owner/Admin/Eingeladener-mit-
        // edit darf Permissions verteilen.
        $project = Project::findOrFail($projectId);
        $this->authorize('update', $project);

        $this->permissions->setForUserOnProject(
            $userId,
            $projectId,
            $permissionIds,
            (int) Auth::user()->id,
        );

        $user = User::findOrFail($userId);
        $permissions = $this->permissions->getCurrentUsersPermissions($userId);

        return redirect()->back()->with([
            'error_code' => 5,
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    /**
     * ajax retrieve user's permission
     */
    public function givePermissionToUser($id): JsonResponse
    {
        [$userId, $projectId] = array_map('intval', explode('_', $id));

        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // Info-Leak — gibt Permission-IDs eines beliebigen Users
        // auf ein beliebiges Projekt heraus. Gate analog
        // setPermissionForUserOnProject.
        $project = Project::findOrFail($projectId);
        $this->authorize('update', $project);

        $data = $this->permissions->getPermissionIdsForUserOnProject($userId, $projectId);

        return response()->json($data);
    }

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

        $log = new LogService;
        $activities = $log->textLog($id);

        return redirect()->back()->with('activities', $activities);
    }

    /**
     * @return Application|Factory|View
     */
    public function getDetails($project, $id)
    {
        $project = Project::findOrFail($project);

        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // getDetails liefert Activity-Log-Diffs des Projekts —
        // sollte nur Lese-berechtigte sehen.
        $this->authorize('view', $project);

        $activities = Activity::where('id', '=', $id)->get();

        $changes = [];

        foreach ($activities as $key => $value) {
            if (isset($value->changes['old'])) {
                foreach ($value->changes['old'] as $k => $property) {
                    if (in_array($k, ['origin', 'copyright'])) {
                        $old = Source::where('id', $property)->where('type', $k)->first();
                        $new = Source::where('id', $value->changes['attributes'][$k])->where('type', $k)->first();

                        $highlight = $this->highlightTextDifference(
                            $old->name,
                            $new->name
                        );

                        $changes[$k] = [
                            'old' => $highlight['old'],
                            'new' => $highlight['new'],
                            'oldId' => $property,
                        ];
                    } else {
                        if (in_array($k, ['url', 'image'])) {
                            $changes[$k] = [
                                'old' => $property,
                                'new' => $value->changes['attributes'][$k],
                            ];
                        } else {

                            $highlight = $this->highlightTextDifference(
                                $property,
                                $value->changes['attributes'][$k]
                            );

                            $changes[$k] = [
                                'old' => $highlight['old'],
                                'new' => $highlight['new'],
                                'noHighlight' => $property,
                            ];
                        }
                    }
                }

                $changes['subjectId'] = $value->subject_id;
                $changes['subjectType'] = $value->subject_type;
            }
        }

        return view('logs.log', compact('changes', 'project'));
    }

    /**
     * Difference between text
     *
     * @return string[]
     */
    public function highlightTextDifference($old, $new)
    {
        $from_start = is_null($old) ? strspn($new, "\0") : strspn($old ^ $new, "\0");
        $from_end = is_null($old) ? strspn(strrev($new), "\0") : strspn(strrev($old) ^ strrev($new), "\0");

        $old_end = strlen($old) - $from_end;
        $new_end = strlen($new) - $from_end;

        $start = substr($new, 0, $from_start);
        $end = substr($new, $new_end);
        $new_diff = substr($new, $from_start, $new_end - $from_start);
        $old_diff = substr($old, $from_start, $old_end - $from_start);

        $new = "$start<span style='background-color:#ccffcc'>$new_diff</span>$end";
        $old = "$start<del style='background-color:#ffcccc'>$old_diff</del>$end";

        return ['old' => $old, 'new' => $new];
    }

    /**
     * Get parent text
     *
     * @return Collection
     */
    public function getParentText($table, $model, $id)
    {
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
        if (isset($request['subjectType']) && ! is_null($request['subjectType'])) {
            $model = $request['subjectType']::findorFail($request['subjectId']);

            if (isset($request['nameReset'])) {
                $model->name = $request['nameReset'];
            }

            if (isset($request['subtitleReset'])) {
                $model->subtitle = $request['subtitleReset'];
            }

            if (isset($request['descriptionReset'])) {
                $model->description = $request['descriptionReset'];
            }

            if (isset($request['copyrightReset'])) {
                $model->copyright = $this->sources->findOrCreateId($request['copyrightReset'], 'Copyright');
            }

            if (isset($request['originReset'])) {
                $model->copyright = $this->sources->findOrCreateId($request['copyrightReset'], 'Origin');
            }

            if (isset($request['textReset'])) {
                $model->text = $request['noHighlight'];
            }

            if (isset($request['imageReset'])) {
                $model->image = $request['imageReset'];
            }

            if (isset($request['urlReset'])) {
                $model->url = $request['urlReset'];
            }

            if (isset($request['sourceReset'])) {
                $model->source = $request['sourceReset'];
            }

            if (isset($request['linkReset'])) {
                $model->link = $request['linkReset'];
            }

            $model->save();
        }

        return redirect(session('links')[2]);
    }

    /**
     * Translate project
     *
     * @return Application|Factory|View
     */
    public function translateCurrentProject($id)
    {
        $project = Project::findOrFail($id);

        // Reader-Frontend-Härtung Juni 2026 (Smoke-Findings nach
        // E.7a-Hotfix). Vorher nur `auth`-Middleware — jeder
        // Reader konnte fremde Project-Inhalte in der
        // Übersetzungs-Maske sehen und (via Sub-POSTs) potentiell
        // mit-bearbeiten. Analog zum editMetaData-Hotfix: Owner
        // ODER Admin ODER Eingeladener mit edit-Permission über
        // ProjectPolicy::update.
        $this->authorize('update', $project);

        App::setlocale('de');
        $data = $this->allData($id);

        return view('translate.index', compact('data'));
    }

    /**
     * @return array
     */
    public function allData($id)
    {
        // Strict-Mode: chapters/entries/mediaContent müssen eager
        // geladen sein, weil die Schleife unten direkt auf
        // $project->chapters, $chapter->entries und
        // $entry->mediaContent zugreift.
        $project = Project::withTranslateTree()->findOrFail($id);
        $data = [];
        $isTranslated = 0;
        $total = 0;

        foreach ($project->chapters as $chapter) {
            $data[$chapter->id] = $chapter;
            if ($chapter->is_translated == 1) {
                $isTranslated++;
            }
            $total++;
            $entries = [];
            foreach ($chapter->entries as $entry) {
                $entries[$entry->id] = $entry;
                if ($entry->is_translated == 1) {
                    $isTranslated++;
                }
                $total++;
                $array = [];
                if (count($entry->mediaContent) > 0) {
                    $collection = $entry->mediaContent->toArray();
                    usort(
                        $collection,
                        function ($item1, $item2) {
                            return $item1['position'] <=> $item2['position'];
                        }
                    );

                    foreach ($collection as $item) {
                        // E.7b Welle 4b (ADR-0022): Diskriminator-Check
                        // auf content_type / content_id (neue Spalten).
                        // Doppelschreibung in den Services hält die alten
                        // gleichwertig bis Welle 4d.
                        if ($item['content_type'] == 'App\Models\Text') {
                            // Strict-Mode: originText/copyrightText
                            // werden unten gleich gelesen, deshalb
                            // gleich mit-eager-laden.
                            $text = Text::with(['originText', 'copyrightText'])
                                ->find($item['content_id']);
                            if ($text) {
                                $text->media_id = $item['id'];
                                $array[] = $text;
                                if ($text->is_translated == 1) {
                                    $isTranslated++;
                                }
                                $total++;

                                if ($text->originText->is_translated == 1) {
                                    $isTranslated++;
                                }
                                $total++;

                                if ($text->copyrightText->is_translated == 1) {
                                    $isTranslated++;
                                }
                                $total++;
                            }
                        } elseif ($item['content_type'] == 'App\Models\Audiovisual') {
                            $audiovisual = Audiovisual::find($item['content_id']);
                            if ($audiovisual) {
                                $audiovisual->media_id = $item['id'];
                                $array[] = $audiovisual;
                                if ($audiovisual->is_translated == 1) {
                                    $isTranslated++;
                                }
                                $total++;

                            }
                        } else {
                            // E.7b Welle 4b: ehemals media_contentable_type
                            // == 'App\Models\Image' (historischer Schiefstand).
                            // Neue Spalte content_type führt sauber Gallery::class.
                            // Strict-Mode: images wird unten gleich
                            // gelesen, deshalb mit-eager-laden.
                            $gallery = Gallery::with('images')->find($item['content_id']);
                            // $image = Image::find($item['content_id']);
                            if ($gallery) {
                                $gallery->media_id = $item['id'];
                                $gallery->image_list = $gallery->images;
                                $array[] = $gallery;

                                if ($gallery->is_translated == 1) {
                                    $isTranslated++;
                                }
                                $total++;
                            }
                        }
                    }
                }

                $entries[$entry->id]->media = $array;
            }
            $data[$chapter->id]->entry = $entries;
        }

        $percentage = 0;

        if ($isTranslated > 0) {
            $percentage = round(($isTranslated / $total) * 100, 2);
        }

        return ['data' => $data, 'percentageOfTranslation' => $percentage, 'projectId' => $id];
    }

    /**
     * User invitation
     *
     * @return Application|Factory|View
     */
    public function inviteUserForProject($id, $projectId)
    {
        $permissions = $this->permissions->getCurrentUsersPermissions($id);

        $user = User::findOrFail($id);
        $role = isset($user->role->userRole->name) ? $user->role->userRole->name : '';
        $permissionForProject = $this->permissions->getSelectedPermissionUser($id, $projectId);
        $listAllPermissions = Permission::orderBy('id', 'ASC')->pluck('name', 'id');

        return \view(
            'users.create',
            compact('user', 'permissions', 'role', 'permissionForProject', 'listAllPermissions', 'projectId')
        );
    }

    /**
     * Check whether input email exists
     * code_error 6: already exist
     * code_error 7: doesn't exist
     *
     * @return RedirectResponse
     */
    protected function checkEmail(Request $request)
    {
        $user = User::where('email', $request->userEmail)->first();

        if ($user) {
            $role = isset($user->role->userRole->name) ? $user->role->userRole->name : '';
            $permissionForRole = [];
            if (isset($user->role->userRole->id)) {
                $permissionForRole = Role::query()
                    ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('roles.id', $user->role->userRole->id)
                    ->pluck('permissions.name');
            }
            $listAllPermissions = Permission::orderBy('id', 'ASC')->pluck('name', 'id');
            $permissionForProject = $user->getAllPermissions()->pluck('name')->toArray();
            $permissionProject = $user->getAllPermissions()->pluck('name')->toArray();

            return Redirect()->back()->with(
                [
                    'error_code' => 6,
                    'user' => $user,
                    'role' => $role,
                    'listAllPermissions' => $listAllPermissions,
                    'permissionForProject' => $permissionForProject,
                    'permissionProject' => $permissionProject,
                    'permissionForRole' => $permissionForRole,
                ]
            );
        } else {
            return Redirect()->back()->with(['error_code' => 7, 'email' => $request->userEmail]);
        }
    }

    /**
     * Delete user from single project
     */
    protected function deleteUserFromProject($userId, $projectId): RedirectResponse
    {
        $this->permissions->removeUserFromProject((int) $userId, (int) $projectId);

        return redirect()->back()->with('success', __('message_edit_project_success'));
    }

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
    public function previewProject(Request $request)
    {
        $parameters = [];

        if (isset($request['colorAccent'])) {
            $parameters['colorAccent'] = $request['colorAccent'];
        }
        if (isset($request['colorChapter'])) {
            $parameters['colorChapter'] = $request['colorChapter'];
        }
        $parameters['backgroundSecond'] = (isset($request['backgroundSecond'])) ? 'hintergrundgrau' : 'hintergrundweiss';
        if (isset($request['collapse'])) {
            $parameters['collapse'] = 1;
        }
        if (isset($request['pdf'])) {
            $parameters['pdf'] = 1;
        }
        $parameters['id'] = $request['project'];
        $project = Project::withPreviewTree()->findOrFail($request['project']);

        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // Web-Preview eines fremden Projekts war ohne Gate erreichbar
        // — Reader-via-URL.
        $this->authorize('view', $project);

        return \view('preview.index', compact('project', 'parameters'));
    }

    /**
     * Generate pdf
     */
    public function downloadPreview(Request $request)
    {

        $parameters = [];

        if (isset($request->colorAccent)) {
            $parameters['colorAccent'] = $request->colorAccent;
        }
        if (isset($request->colorChapter)) {
            $parameters['colorChapter'] = $request->colorChapter;
        }
        $parameters['backgroundSecond'] = (isset($request->backgroundSecond)) ? 'hintergrundgrau' : 'hintergrundweiss';
        if (isset($request->collapse)) {
            $parameters['collapse'] = $request->collapse;
        }
        if (isset($request->pdf)) {
            $parameters['pdf'] = 1;
        }

        $project = Project::withPreviewTree()->findOrFail($request->id);

        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // PDF-Download fremder Projekte ohne Gate war erreichbar.
        $this->authorize('view', $project);

        $html = View('preview.pdf', compact('project', 'parameters'))->render();

        $options = new Options;
        $options->setChroot(['/var/www/html/public/']);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser
        $dompdf->stream();

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
                $content = $project->terms;
                $type = 'copyright';
            } else {
                $content = $project->imprint;
                $type = 'policy';
            }
        }

        return \view('preview.copyright', compact('project', 'parameters', 'content', 'type'));
    }
}
