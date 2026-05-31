<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Goal;

class GoalSeeder extends Seeder
{
    public function run(): void
    {
        $goals = [
            [
                'label' => 'First Investment',
                'icon' => '🚀',
                'target_amount' => 5000,
                'is_primary' => false,
                'sort_order' => 1,
            ],
            [
                'label' => 'Open Bank Account',
                'icon' => '🏦',
                'target_amount' => 50000,
                'is_primary' => true,
                'sort_order' => 2,
            ],
            [
                'label' => 'Phase 2 Ready',
                'icon' => '⚙',
                'target_amount' => 100000,
                'is_primary' => false,
                'sort_order' => 3,
            ],
            [
                'label' => 'Legal Registration',
                'icon' => '📋',
                'target_amount' => 150000,
                'is_primary' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($goals as $goal) {
            Goal::create($goal);
        }
    }
}
