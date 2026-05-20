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
}
