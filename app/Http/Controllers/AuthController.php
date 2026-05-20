<?php

namespace App\Http\Controllers;

use App\Models\Member;
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
            'email' => 'required|email|unique:members,email',
            'phone' => 'nullable|string',
        ]);

        \App\Models\PendingRegistration::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Registration request submitted. Awaiting approval.']);
    }

    public function redirectToGoogle()
    {
        return \Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = \Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/?error=google_auth_failed');
        }

        $member = Member::where('google_email', $googleUser->email)
                        ->orWhere('google_uid', $googleUser->id)
                        ->where('status', 'active')
                        ->first();

        if (!$member) {
            return redirect('/?error=not_a_member');
        }

        $member->update(['google_uid' => $googleUser->id]);
        $token = $member->createToken('sipr-google')->plainTextToken;
        ActivityService::log('google_login', "{$member->name} signed in via Google", $member->id);

        return redirect('/#google-token=' . $token . '&member=' . urlencode(json_encode([
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $member->role,
        ])));
    }

    public function googleLogin(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        // Extract email and google_uid from request
        // In production, verify the token with Google API
        $email = $request->get('email');
        $googleUid = $request->get('google_uid');

        if (!$email || !$googleUid) {
            return response()->json(['error' => 'Invalid token data'], 400);
        }

        $member = Member::where('email', $email)
                        ->orWhere('google_uid', $googleUid)
                        ->where('status', 'active')
                        ->first();

        if (!$member) {
            return response()->json(['error' => 'Member not found or inactive'], 401);
        }

        $member->update(['google_uid' => $googleUid]);
        $token = $member->createToken('sipr-google')->plainTextToken;

        ActivityService::log('google_login', "{$member->name} signed in via Google", $member->id);

        return response()->json([
            'token' => $token,
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
            ]
        ]);
    }
}
