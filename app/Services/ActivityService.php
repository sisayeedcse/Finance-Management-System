<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\ActivityLog;

class ActivityService
{
    public static function log(string $action, string $description, string $memberId): void
    {
        $member = \App\Models\Member::find($memberId);
        ActivityLog::create([
            'action' => $action,
            'description' => $description,
            'performed_by' => $memberId,
            'performed_by_name' => $member?->name ?? 'Unknown',
            'created_at' => now(),
        ]);
    }
}
