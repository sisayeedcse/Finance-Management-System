<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'collected_kg', 'sold_kg', 'revenue', 'note', 'collected_at', 'recorded_by'
    ];

    protected $casts = [
        'collected_kg' => 'decimal:3',
        'sold_kg' => 'decimal:3',
        'revenue' => 'decimal:2',
        'collected_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
