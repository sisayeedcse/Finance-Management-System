<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\ActivityService;
use App\Http\Requests\StoreProjectRequest;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::orderByDesc('created_at')->get();
        return response()->json(['data' => $projects]);
    }

    public function show(string $id)
    {
        $project = Project::findOrFail($id);
        return response()->json($project);
    }

    public function store(StoreProjectRequest $request)
    {
        $project = Project::create($request->only([
            'name',
            'description',
            'type',
            'status',
            'source_id',
            'capital',
            'returned',
            'expected',
            'team',
            'started_at',
            'capitalSource',
            'projectManagerId',
            'projectManagerName',
            'teamEntries',
            'teamMembers',
            'collections',
            'sales',
            'buyers',
            'projectExpenses',
            'capitalEntries',
            'phases',
            'partner',
            'amount',
            'capitalDeployed',
            'expectedReturn',
            'actualReturn',
            'sector',
            'date',
            'notes',
            'source_payload',
        ]));
        ActivityService::log('create_project', "Created project: {$project->name}", $request->user()->id);

        return response()->json($project, 201);
    }

    public function update(string $id, Request $request)
    {
        $project = Project::findOrFail($id);
        $project->update($request->only([
            'name',
            'description',
            'type',
            'status',
            'source_id',
            'capital',
            'returned',
            'expected',
            'team',
            'started_at',
            'capitalSource',
            'projectManagerId',
            'projectManagerName',
            'teamEntries',
            'teamMembers',
            'collections',
            'sales',
            'buyers',
            'projectExpenses',
            'capitalEntries',
            'phases',
            'partner',
            'amount',
            'capitalDeployed',
            'expectedReturn',
            'actualReturn',
            'sector',
            'date',
            'notes',
            'source_payload',
        ]));
        ActivityService::log('update_project', "Updated project: {$project->name}", $request->user()->id);
        return response()->json($project);
    }

    public function destroy(string $id, Request $request)
    {
        $project = Project::findOrFail($id);
        ActivityService::log('delete_project', "Deleted project: {$project->name}", $request->user()->id);
        $project->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function milestones(string $id)
    {
        $project = Project::findOrFail($id);
        return response()->json($project->milestones()->orderBy('sort_order')->get());
    }

    public function storeMilestone(string $id, Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
        ]);

        $project = Project::findOrFail($id);
        $milestone = $project->milestones()->create([
            'title' => $request->title,
            'sort_order' => $project->milestones()->max('sort_order') + 1,
        ]);

        return response()->json($milestone, 201);
    }

    public function updateMilestone(string $id, Request $request)
    {
        $milestone = ProjectMilestone::findOrFail($id);
        $milestone->update($request->only(['achieved', 'achieved_at']));
        ActivityService::log('update_milestone', "Updated milestone {$milestone->title}", $request->user()->id);
        return response()->json($milestone);
    }
}
