<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FirestoreBackupSqlSeeder::class,
            MemberSeeder::class,
            TransactionSeeder::class,
            ProjectSeeder::class,
            GoalSeeder::class,
            ExpenseSeeder::class,
        ]);
    }
}
