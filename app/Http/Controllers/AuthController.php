<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PendingRegistration;
use App\Services\ActivityService;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {

        $member = Member::where('email', $request->email)
                        ->where('status', 'active')
                        ->first();

        if (!$member || !Hash::check($request->password, $member->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $member->createToken('sipr-app')->plainTextToken;
        ActivityService::log('login', "{$member->name} signed in", $member->id);

        return response()->json([
            'token' => $token,
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
                'title' => $member->title,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $member = $request->user();
        ActivityService::log('logout', "{$member->name} signed out", $member->id);
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $member = $request->user();
        return response()->json([
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $member->role,
            'title' => $member->title,
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $member = $request->user();
        $member->update(['password' => Hash::make($request->password)]);
        ActivityService::log('change_password', "{$member->name} changed password", $member->id);

        return response()->json(['message' => 'Password updated']);
    }

    public function requestRegistration(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:members,email|unique:pending_registrations,email',
            'phone' => 'nullable|string',
            'invite_code' => 'required|string|max:50',
            'password' => 'required|string|min:6',
        ]);

        PendingRegistration::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'invite_code' => $request->invite_code,
            'password' => Hash::make($request->password),
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Registration request submitted. Awaiting approval.']);
    }

    public function redirectToGoogle()
    {
        return redirect('/?error=google_signin_disabled');
    }

    public function handleGoogleCallback()
    {
        return redirect('/?error=google_signin_disabled');
    }

    public function googleLogin(Request $request)
    {
        return response()->json([
            'error' => 'Google sign-in is disabled for this installation.'
        ], 410);
    }
}
