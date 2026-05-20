<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        Expense::create([
            'amount' => 8000,
            'description' => 'For SIPR official web app',
            'expense_date' => '2026-05-01',
            'recorded_by' => 'SIPR26-JH-6729',
        ]);
    }
}
