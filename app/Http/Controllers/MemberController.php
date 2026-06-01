<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\ActivityService;
use App\Services\MemberIdService;
use App\Http\Requests\UpdateMemberRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::where('status', 'active')
            ->orderByRaw("FIELD(role,'admin','finance','secretary','member')")
            ->get(['id', 'name', 'title', 'role', 'email', 'google_email', 'phone', 'photo', 'address', 'monthly_due', 'google_uid', 'locked']);
        return response()->json(['data' => $members]);
    }

    public function show(string $id)
    {
        $member = Member::findOrFail($id);
        return response()->json($member);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:members,email|unique:pending_registrations,email',
            'phone' => 'nullable|string|max:30',
            'title' => 'nullable|string|max:100',
            'role' => 'in:admin,finance,secretary,member',
            'monthly_due' => 'numeric|min:0',
        ]);

        $memberId = MemberIdService::generate($request->name);
        $temporaryPassword = $request->input('password') ?: Str::random(16);

        $member = Member::create([
            'id' => $memberId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'title' => $request->title,
            'role' => $request->role ?? 'member',
            'monthly_due' => $request->monthly_due ?? 500,
            'password' => Hash::make($temporaryPassword),
            'status' => 'active',
        ]);

        ActivityService::log('add_member', "Added member {$member->name} ({$member->id})", $request->user()->id);

        return response()->json([
            'data' => $member,
            'temporary_password' => $temporaryPassword,
        ], 201);
    }

    public function update(string $id, UpdateMemberRequest $request)
    {
        $member = Member::findOrFail($id);

        if ($member->locked && $request->has('title')) {
            return response()->json(['error' => 'Title is locked for this member'], 422);
        }

        $member->update($request->only(['name', 'email', 'phone', 'title', 'role', 'monthly_due', 'photo']));
        ActivityService::log('save_member', "Updated member {$member->name}", $request->user()->id);

        return response()->json($member);
    }

    public function reset(Request $request)
    {
        $credentials = [];

        Member::where('status', 'active')->update([
            'phone' => null,
            'google_uid' => null,
            'google_email' => null,
            'photo' => null,
        ]);

        Member::where('status', 'active')->get()->each(function (Member $member) use (&$credentials) {
            $temporaryPassword = Str::random(16);
            $member->update([
                'password' => Hash::make($temporaryPassword),
            ]);

            $credentials[] = [
                'id' => $member->id,
                'email' => $member->email,
                'temporary_password' => $temporaryPassword,
            ];
        });

        ActivityService::log('reset_members', "Reset all member profiles", $request->user()->id);
        return response()->json([
            'message' => 'Members reset',
            'temporary_passwords' => $credentials,
        ]);
    }

    public function unlinkGoogle(string $id, Request $request)
    {
        $member = Member::findOrFail($id);
        $member->update(['google_uid' => null, 'google_email' => null]);
        ActivityService::log('unlink_google', "Unlinked Google account for {$member->name}", $request->user()->id);
        return response()->json(['message' => 'Google account unlinked']);
    }

    public function linkGoogle(string $id, Request $request)
    {
        $member = Member::findOrFail($id);
        $user = $request->user();

        if ($user->id !== $member->id && ($user->role ?? '') !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'google_uid' => 'required|string',
            'google_email' => 'required|email',
        ]);

        $member->update([
            'google_uid' => $request->get('google_uid'),
            'google_email' => $request->get('google_email'),
        ]);

        ActivityService::log('link_google', "Linked Google account for {$member->name}", $user->id);
        return response()->json($member);
    }

    public function about()
    {
        $members = Member::where('status', 'active')->get();
        return response()->json([
            'name' => 'SIPR Group',
            'tagline' => 'Invest. Grow. Prosper.',
            'founded' => 'February 2026',
            'location' => 'Boalkhai, Chittagong, Bangladesh',
            'member_count' => $members->count(),
            'mission' => 'Build a self-sustaining investment group where every member saves, invests, and grows wealth through collective action.',
            'vision' => 'Become a formal business within 12 months operating a profitable plastic recycling business along the Karnafuli River.',
            'members' => $members,
        ]);
    }
}
