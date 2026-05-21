<?php

namespace App\Http\Controllers;

use App\Models\ProjectCollection;
use App\Models\Project;
use App\Services\ActivityService;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(string $projectId)
    {
        $collections = ProjectCollection::where('project_id', $projectId)->orderByDesc('collected_at')->get();
        return response()->json(['data' => $collections]);
    }

    public function store(string $projectId, Request $request)
    {
        $request->validate([
            'collected_kg' => 'required|numeric|min:0',
            'sold_kg' => 'required|numeric|min:0',
            'revenue' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'collected_at' => 'required|date',
        ]);

        $project = Project::findOrFail($projectId);
        $collection = ProjectCollection::create([
            'project_id' => $projectId,
            'collected_kg' => $request->collected_kg,
            'sold_kg' => $request->sold_kg,
            'revenue' => $request->revenue,
            'note' => $request->note,
            'collected_at' => $request->collected_at,
            'recorded_by' => $request->user()->id,
        ]);

        ActivityService::log('add_collection', "Added collection for {$project->name}", $request->user()->id);

        return response()->json($collection, 201);
    }

    // Generic store endpoint accepting project_id in payload
    public function storeGeneric(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'collected_kg' => 'required|numeric|min:0',
            'sold_kg' => 'required|numeric|min:0',
            'revenue' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'collected_at' => 'required|date',
        ]);

        $projectId = $request->get('project_id');
        $project = Project::findOrFail($projectId);

        $collection = ProjectCollection::create([
            'project_id' => $projectId,
            'collected_kg' => $request->collected_kg,
            'sold_kg' => $request->sold_kg,
            'revenue' => $request->revenue,
            'note' => $request->note,
            'collected_at' => $request->collected_at,
            'recorded_by' => $request->user()->id,
        ]);

        ActivityService::log('add_collection', "Added collection for {$project->name}", $request->user()->id);

        return response()->json($collection, 201);
    }
}
