<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\ProjectCollection;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Project 1: Flour Mill
        $project1 = Project::create([
            'name' => 'flour mill',
            'description' => 'buy masala in low price, and selling it to locals',
            'type' => 'local buyer',
            'status' => 'active',
            'capital' => 0,
            'returned' => 0,
            'expected' => 0,
            'team' => 'all',
            'started_at' => '2026-04-02',
        ]);

        // Project 2: Plastic Recycle Phase 1 – Collection
        $project2 = Project::create([
            'name' => 'plastic recycle phase 1 – collection',
            'description' => 'Recycling plastic collection project',
            'type' => 'recycling',
            'status' => 'active',
            'capital' => 0,
            'returned' => 1225,
            'expected' => 0,
            'team' => 'all',
            'started_at' => '2026-04-02',
        ]);

        // Add collection data for plastic recycle project
        ProjectCollection::create([
            'project_id' => $project2->id,
            'collected_kg' => 40.0,
            'sold_kg' => 35.0,
            'revenue' => 1225,
            'note' => 'First batch',
            'collected_at' => '2026-05-01',
            'recorded_by' => 'SIPR26-JH-6729',
        ]);
    }
}
