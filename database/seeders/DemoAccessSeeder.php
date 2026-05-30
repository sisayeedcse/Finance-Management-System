<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccessSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'Demo@12345';

        $members = [
            [
                'id' => 'DEMO-AD-0001',
                'name' => 'Demo Admin',
                'email' => 'demo.admin@sipr.local',
                'phone' => null,
                'title' => 'Administrator',
                'role' => 'admin',
                'locked' => false,
                'status' => 'active',
                'google_uid' => null,
                'google_email' => null,
                'monthly_due' => 500,
                'password' => Hash::make($password),
            ],
            [
                'id' => 'DEMO-FI-0001',
                'name' => 'Demo Finance',
                'email' => 'demo.finance@sipr.local',
                'phone' => null,
                'title' => 'Finance',
                'role' => 'finance',
                'locked' => false,
                'status' => 'active',
                'google_uid' => null,
                'google_email' => null,
                'monthly_due' => 500,
                'password' => Hash::make($password),
            ],
            [
                'id' => 'DEMO-SE-0001',
                'name' => 'Demo Secretary',
                'email' => 'demo.secretary@sipr.local',
                'phone' => null,
                'title' => 'Secretary',
                'role' => 'secretary',
                'locked' => false,
                'status' => 'active',
                'google_uid' => null,
                'google_email' => null,
                'monthly_due' => 500,
                'password' => Hash::make($password),
            ],
            [
                'id' => 'DEMO-ME-0001',
                'name' => 'Demo Member',
                'email' => 'demo.member@sipr.local',
                'phone' => null,
                'title' => 'Member',
                'role' => 'member',
                'locked' => false,
                'status' => 'active',
                'google_uid' => null,
                'google_email' => null,
                'monthly_due' => 500,
                'password' => Hash::make($password),
            ],
        ];

        foreach ($members as $memberData) {
            Member::updateOrCreate(
                ['email' => $memberData['email']],
                $memberData,
            );
        }
    }
}