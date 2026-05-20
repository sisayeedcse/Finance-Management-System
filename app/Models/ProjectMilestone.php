<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'title', 'achieved', 'achieved_at', 'sort_order'
    ];

    protected $casts = [
        'achieved' => 'boolean',
        'achieved_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
