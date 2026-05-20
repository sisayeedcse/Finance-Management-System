<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $transactions = [
            [
                'member_id' => 'SIPR26-FA-4934',
                'type' => 'deposit',
                'amount' => 500,
                'note' => null,
                'date' => '2026-01-01',
                'created_by' => 'SIPR26-JH-6729',
            ],
            [
                'member_id' => 'SIPR26-JH-6729',
                'type' => 'deposit',
                'amount' => 1000,
                'note' => null,
                'date' => '2026-01-01',
                'created_by' => 'SIPR26-JH-6729',
            ],
            [
                'member_id' => 'SIPR26-AK-2307',
                'type' => 'deposit',
                'amount' => 500,
                'note' => null,
                'date' => '2026-01-01',
                'created_by' => 'SIPR26-JH-6729',
            ],
            [
                'member_id' => 'SIPR26-SH-4688',
                'type' => 'deposit',
                'amount' => 500,
                'note' => null,
                'date' => '2026-01-01',
                'created_by' => 'SIPR26-JH-6729',
            ],
            [
                'member_id' => 'SIPR26-SA-9514',
                'type' => 'deposit',
                'amount' => 500,
                'note' => null,
                'date' => '2026-04-02',
                'created_by' => 'SIPR26-JH-6729',
            ],
            [
                'member_id' => 'SIPR26-AB-5111',
                'type' => 'deposit',
                'amount' => 500,
                'note' => null,
                'date' => '2026-04-08',
                'created_by' => 'SIPR26-JH-6729',
            ],
            [
                'member_id' => 'SIPR26-IM-2838',
                'type' => 'deposit',
                'amount' => 500,
                'note' => null,
                'date' => '2026-04-22',
                'created_by' => 'SIPR26-JH-6729',
            ],
            [
                'member_id' => 'SIPR26-JH-6729',
                'type' => 'expense',
                'amount' => 8000,
                'note' => 'For SIPR official web app',
                'date' => '2026-05-01',
                'created_by' => 'SIPR26-JH-6729',
            ],
        ];

        foreach ($transactions as $tx) {
            Transaction::create($tx);
        }
    }
}
