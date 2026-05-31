<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

class TempSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'jaziro@sipr.com';
        $member = Member::where('email', $email)->first() ?: Member::first();

        if (!$member) {
            $this->command->info('No member found to create token for.');
            return;
        }

        $member->password = Hash::make('secret123');
        $member->save();

        $token = $member->createToken('temp-seeder')->plainTextToken;
        $this->command->info('TEMP_TOKEN:' . $token);
    }
}
