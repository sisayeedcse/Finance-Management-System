<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        return response()->json(['data' => ActivityLog::orderByDesc('id')->limit(100)->get()]);
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
            'performed_by' => $request->user() ? $request->user()->id : null,
            'performed_by_name' => $request->user() ? $request->user()->name : 'Unknown',
            'created_at' => now(),
        ]);

        return response()->json($log, 201);
    }
}
