<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Goal::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'target_amount' => 'required|numeric|min:0',
            'is_primary' => 'boolean',
            'sort_order' => 'integer'
        ]);

        $goal = Goal::create([
            'label' => $validated['label'],
            'icon' => $validated['icon'] ?? '🎯',
            'target_amount' => $validated['target_amount'],
            'is_primary' => $validated['is_primary'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json($goal, 201);
    }

    public function update(Request $request, $id)
    {
        $goal = Goal::findOrFail($id);

        $validated = $request->validate([
            'label' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'target_amount' => 'sometimes|required|numeric|min:0',
            'is_primary' => 'boolean',
            'sort_order' => 'integer'
        ]);

        $goal->update($validated);

        return response()->json($goal);
    }

    public function destroy($id)
    {
        $goal = Goal::findOrFail($id);
        $goal->delete();
        
        return response()->json(['message' => 'Goal deleted successfully']);
    }
}
