<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PendingRegistration;
use App\Services\ActivityService;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {
            $member = Member::where('email', $request->email)
                ->where('status', 'active')
                ->first();

            if (!$member || blank($member->password) || !Hash::check($request->password, $member->password)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                    'error' => 'Invalid credentials',
                ], 401);
            }

            $token = $member->createToken('sipr-app')->plainTextToken;
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => 'Login failed on the server. Please check the server logs.',
            ], 500);
        }

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
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/?error=google_auth_failed');
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        $member = $this->resolveGoogleMember([
            'email' => $email,
            'google_uid' => $googleId,
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $email,
            'photo' => $googleUser->getAvatar(),
        ]);

        $token = $member->createToken('sipr-google')->plainTextToken;

        $memberData = json_encode([
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $member->role,
        ]);

        // Redirect with token in fragment to avoid exposing it in server logs or query
        return redirect('/#google-token=' . $token . '&member=' . urlencode($memberData));
    }

    public function googleLogin(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        $idToken = $request->get('id_token');

        // Verify token with Google's tokeninfo endpoint
        try {
            $resp = Http::get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to verify token'], 500);
        }

        if (! $resp->ok()) {
            return response()->json(['error' => 'Invalid ID token'], 401);
        }

        $data = $resp->json();

        // Verify audience matches configured client id (if present)
        $clientId = Config::get('services.google.client_id');
        if ($clientId && isset($data['aud']) && $data['aud'] !== $clientId) {
            return response()->json(['error' => 'Token audience mismatch'], 401);
        }

        $email = $data['email'] ?? null;
        $googleUid = $data['sub'] ?? null;

        if (! $email) {
            return response()->json(['error' => 'Token did not contain email'], 401);
        }

        $member = $this->resolveGoogleMember([
            'email' => $email,
            'google_uid' => $googleUid,
            'name' => $data['name'] ?? $data['given_name'] ?? $email,
            'photo' => $data['picture'] ?? null,
        ]);
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

    private function resolveGoogleMember(array $profile): Member
    {
        $email = $profile['email'] ?? null;
        $googleUid = $profile['google_uid'] ?? null;

        $member = Member::query()
            ->where('status', 'active')
            ->where(function ($query) use ($email, $googleUid) {
                if ($googleUid) {
                    $query->where('google_uid', $googleUid);
                }

                if ($email) {
                    $query->orWhere('google_email', $email)
                        ->orWhere('email', $email);
                }
            })
            ->first();

        if ($member) {
            $member->update([
                'google_uid' => $googleUid ?: $member->google_uid,
                'google_email' => $email ?: $member->google_email,
                'photo' => $profile['photo'] ?? $member->photo,
                'name' => $member->name ?: ($profile['name'] ?? $member->name),
            ]);

            return $member;
        }

        $member = Member::create([
            'id' => $this->generateMemberId(),
            'name' => $profile['name'] ?? $email ?? 'Member',
            'email' => $email ?? sprintf('%s@sipr.local', Str::lower(Str::random(12))),
            'phone' => null,
            'title' => 'Member',
            'role' => 'member',
            'locked' => false,
            'status' => 'active',
            'google_uid' => $googleUid,
            'google_email' => $email,
            'photo' => $profile['photo'] ?? null,
            'monthly_due' => 500,
            'password' => null,
        ]);

        return $member;
    }

    private function generateMemberId(): string
    {
        return Str::upper(Str::random(20));
    }
}
