<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        return response()->json(['data' => ActivityLog::orderByDesc('created_at')->limit(100)->get()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'action' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $log = ActivityLog::create([
            'action' => $request->action,
            'description' => $request->description ?? null,
            'member_id' => $request->user() ? $request->user()->id : null,
            'created_at' => now(),
        ]);

        return response()->json($log, 201);
    }
}
