<?php

namespace Biigle\Modules\Transects\Http\Controllers;

use Biigle\Role;
use Biigle\Project;
use Biigle\Transect;
use Carbon\Carbon;
use Biigle\LabelTree;
use Biigle\MediaType;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Biigle\Http\Controllers\Views\Controller;

class TransectController extends Controller
{
    /**
     * Shows the create transect page.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $project = Project::findOrFail($request->input('project'));
        $this->authorize('update', $project);

        return view('transects::create')
            ->with('project', $project)
            ->with('mediaTypes', MediaType::all());
    }

    /**
     * Shows the transect index page.
     *
     * @param Guard $auth
     * @param int $id transect ID
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Guard $auth, $id)
    {
        $transect = Transect::findOrFail($id);
        $this->authorize('access', $transect);
        $user = $auth->user();

        if ($user->isAdmin) {
            // admins have no restrictions
            $projects = $transect->projects;
        } else {
            // all projects that the user and the transect have in common
            $projects = $user->projects()
                ->whereIn('id', function ($query) use ($transect) {
                    $query->select('project_transect.project_id')
                        ->from('project_transect')
                        ->join('project_user', 'project_transect.project_id', '=', 'project_user.project_id')
                        ->where('project_transect.transect_id', $transect->id)
                        ->whereIn('project_user.project_role_id', [Role::$editor->id, Role::$admin->id]);
                })
                ->get();
        }

        // all label trees that are used by all projects which are visible to the user
        $labelTrees = LabelTree::with('labels')
            ->select('id', 'name')
            ->whereIn('id', function ($query) use ($projects) {
                $query->select('label_tree_id')
                    ->from('label_tree_project')
                    ->whereIn('project_id', $projects->pluck('id'));
            })
            ->get();

        $imageIds = $transect->orderedImages()
            ->pluck('uuid', 'id');

        return view('transects::index')
            ->with('user', $user)
            ->with('transect', $transect)
            ->with('labelTrees', $labelTrees)
            ->with('projects', $projects)
            ->with('imageIds', $imageIds);
    }

    /**
     * Shows the transect edit page.
     *
     * @param int $id transect ID
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $transect = Transect::with('projects')->findOrFail($id);
        $this->authorize('update', $transect);
        $sessions = $transect->annotationSessions()->with('users')->get();

        return view('transects::edit', [
            'transect' => $transect,
            'images' => $transect->orderedImages()->pluck('filename', 'id'),
            'mediaTypes' => MediaType::all(),
            'annotationSessions' => $sessions,
            'today' => Carbon::today()
        ]);
    }
}
