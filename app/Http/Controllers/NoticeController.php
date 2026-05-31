<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\Member;
use App\Services\ActivityService;
use App\Http\Requests\StoreNoticeRequest;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'announcement');
        $memberNames = Member::pluck('name', 'id');

        $notices = Notice::where('type', $type)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($notice) use ($memberNames) {
                $row = $notice->toArray();
                $row['posted_by_name'] = $memberNames[$notice->posted_by] ?? $notice->posted_by;

                return $row;
            });

        return response()->json($notices);
    }

    public function store(StoreNoticeRequest $request)
    {
        $notice = Notice::create([
            'type' => $request->type,
            'title' => $request->title,
            'body' => $request->body,
            'posted_by' => $request->user()->id,
        ]);

        ActivityService::log('post_notice', "Posted {$request->type}: {$request->title}", $request->user()->id);
        return response()->json($notice, 201);
    }

    public function destroy(string $id, Request $request)
    {
        $notice = Notice::findOrFail($id);
        ActivityService::log('delete_notice', "Deleted notice {$id}", $request->user()->id);
        $notice->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
