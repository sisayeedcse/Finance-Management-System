<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PendingRegistration;
use App\Services\MemberIdService;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ControlPanelController extends Controller
{
    public function index()
    {
        return response()->json([
            'roles' => [
                'admin' => 'Full access — add/delete/edit everything, manage members, control panel',
                'finance' => 'Add & edit transactions and payments, export CSV',
                'secretary' => 'Post announcements, upload documents',
                'member' => 'View only — cannot add or edit anything',
            ],
        ]);
    }

    public function pendingRegistrations()
    {
        $pending = PendingRegistration::where('status', 'pending')->get();
        return response()->json(['data' => $pending]);
    }

    public function approve(string $id, Request $request)
    {
        $registration = PendingRegistration::findOrFail($id);

        if ($registration->status !== 'pending') {
            return response()->json(['error' => 'Registration is no longer pending'], 422);
        }

        $request->validate([
            'role' => 'required|in:admin,finance,secretary,member',
        ]);

        if (Member::where('email', $registration->email)->exists()) {
            return response()->json(['error' => 'A member with this email already exists'], 409);
        }

        $memberId = MemberIdService::generate($registration->name);
        $passwordHash = $registration->password;
        $temporaryPassword = null;

        if (!$passwordHash) {
            $temporaryPassword = Str::random(16);
            $passwordHash = Hash::make($temporaryPassword);
        }

        Member::create([
            'id' => $memberId,
            'name' => $registration->name,
            'email' => $registration->email,
            'phone' => $registration->phone,
            'password' => $passwordHash,
            'status' => 'active',
            'role' => $request->role,
        ]);

        $registration->update([
            'status' => 'approved',
            'member_id' => $memberId,
            'approved_role' => $request->role,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        ActivityService::log('approve_registration', "Approved {$registration->name}", $request->user()->id);

        return response()->json([
            'message' => 'Approved',
            'temporary_password' => $temporaryPassword,
        ]);
    }

    public function reject(string $id, Request $request)
    {
        $registration = PendingRegistration::findOrFail($id);
        $registration->update(['status' => 'rejected']);
        ActivityService::log('reject_registration', "Rejected {$registration->name}", $request->user()->id);

        return response()->json(['message' => 'Rejected']);
    }

    public function destroy(string $id, Request $request)
    {
        $registration = PendingRegistration::findOrFail($id);

        if ($registration->status === 'approved') {
            return response()->json(['error' => 'Approved registrations cannot be deleted'], 422);
        }

        ActivityService::log('delete_registration', "Deleted {$registration->name}", $request->user()->id);
        $registration->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
