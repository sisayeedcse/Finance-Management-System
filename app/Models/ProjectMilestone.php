<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'project_id', 'title', 'note', 'achieved', 'achieved_at', 'sort_order', 'source_payload'
    ];

    protected $casts = [
        'achieved' => 'boolean',
        'achieved_at' => 'date',
        'source_payload' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
