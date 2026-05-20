<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'type', 'status', 'capital', 'returned', 'expected', 'team', 'started_at'
    ];

    protected $casts = [
        'capital' => 'decimal:2',
        'returned' => 'decimal:2',
        'expected' => 'decimal:2',
        'started_at' => 'date',
    ];

    public function collections()
    {
        return $this->hasMany(ProjectCollection::class, 'project_id');
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id');
    }
}
