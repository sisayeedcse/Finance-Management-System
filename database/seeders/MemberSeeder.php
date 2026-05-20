<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'id' => 'SIPR26-JH-6729',
                'name' => 'Jahed Aziz',
                'email' => 'jaziro@sipr.com',
                'phone' => '01700000001',
                'title' => 'President & Founder',
                'role' => 'admin',
                'locked' => true,
                'status' => 'active',
                'google_email' => 'jaazbusinessmail@gmail.com',
                'monthly_due' => 2000,
                'password' => Hash::make('password'),
            ],
            [
                'id' => 'SIPR26-SH-4688',
                'name' => 'Shoeb',
                'email' => 'shoeb@sipr.com',
                'phone' => '+8801309-705306',
                'title' => 'Treasurer',
                'role' => 'finance',
                'locked' => false,
                'status' => 'active',
                'google_email' => 'shoebjibran123@gmail.com',
                'monthly_due' => 500,
                'password' => Hash::make('password'),
            ],
            [
                'id' => 'SIPR26-RI-5469',
                'name' => 'Rizvi',
                'email' => 'rizvi@sipr.com',
                'phone' => '+8801726-277686',
                'title' => 'Accounts Officer',
                'role' => 'finance',
                'locked' => false,
                'status' => 'active',
                'google_email' => 'asayedrizvi@gmail.com',
                'monthly_due' => 500,
                'password' => Hash::make('password'),
            ],
            [
                'id' => 'SIPR26-SA-9514',
                'name' => 'Sajid',
                'email' => 'sazid@sipr.com',
                'phone' => '+8801845-526909',
                'title' => 'Fund Collector',
                'role' => 'member',
                'locked' => false,
                'status' => 'active',
                'google_email' => 'huntertreasure612@gmail.com',
                'monthly_due' => 500,
                'password' => Hash::make('password'),
            ],
            [
                'id' => 'SIPR26-FA-4934',
                'name' => 'Fahim',
                'email' => 'fahim@sipr.com',
                'phone' => '+8801845-123432',
                'title' => 'Secretary',
                'role' => 'secretary',
                'locked' => false,
                'status' => 'active',
                'google_email' => 'mhdfahim001@gmail.com',
                'monthly_due' => 500,
                'password' => Hash::make('password'),
            ],
            [
                'id' => 'SIPR26-AK-2307',
                'name' => 'Akib Ahmed',
                'email' => 'akib@sipr.com',
                'phone' => '+8801832-903248',
                'title' => 'Adviser',
                'role' => 'member',
                'locked' => false,
                'status' => 'active',
                'google_email' => 'ahmedakib343@gmail.com',
                'monthly_due' => 500,
                'password' => Hash::make('password'),
            ],
            [
                'id' => 'SIPR26-AB-5111',
                'name' => 'Abu Tajbit',
                'email' => 'ahad@sipr.com',
                'phone' => '+8801648-340457',
                'title' => 'Member',
                'role' => 'member',
                'locked' => false,
                'status' => 'active',
                'google_email' => 'awattajbi400@gmail.com',
                'monthly_due' => 500,
                'password' => Hash::make('password'),
            ],
            [
                'id' => 'SIPR26-IM-2838',
                'name' => 'Imtiaz',
                'email' => 'imtiaz@sipr.com',
                'phone' => '+8801610-236253',
                'title' => 'Adviser',
                'role' => 'member',
                'locked' => false,
                'status' => 'active',
                'google_email' => 'imtiaz01880@gmail.com',
                'monthly_due' => 500,
                'password' => Hash::make('password'),
            ],
            [
                'id' => 'SIPR26-WD-1920',
                'name' => 'Web developer',
                'email' => 'tech.adayaed@gmail.com',
                'phone' => '0561231673',
                'title' => 'Web Developer',
                'role' => 'admin',
                'locked' => false,
                'status' => 'active',
                'google_email' => null,
                'monthly_due' => 500,
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($members as $member) {
            Member::create($member);
        }
    }
}
