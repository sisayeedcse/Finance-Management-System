<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PendingRegistration;
use App\Services\MemberIdService;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        $memberId = MemberIdService::generate($registration->name);
        Member::create([
            'id' => $memberId,
            'name' => $registration->name,
            'email' => $registration->email,
            'phone' => $registration->phone,
            'password' => Hash::make('password'),
            'status' => 'active',
            'role' => 'member',
        ]);

        $registration->update(['status' => 'approved', 'member_id' => $memberId]);
        ActivityService::log('approve_registration', "Approved {$registration->name}", $request->user()->id);

        return response()->json(['message' => 'Approved']);
    }

    public function reject(string $id, Request $request)
    {
        $registration = PendingRegistration::findOrFail($id);
        $registration->update(['status' => 'rejected']);
        ActivityService::log('reject_registration', "Rejected {$registration->name}", $request->user()->id);

        return response()->json(['message' => 'Rejected']);
    }
}
