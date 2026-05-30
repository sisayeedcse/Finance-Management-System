<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'collected_kg', 'sold_kg', 'revenue', 'note', 'plastic_type',
        'price_per_kg', 'sale_note', 'source', 'unit', 'recorded_by_name', 'collected_at',
        'added_at', 'recorded_by', 'source_payload'
    ];

    protected $casts = [
        'collected_kg' => 'decimal:3',
        'sold_kg' => 'decimal:3',
        'revenue' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'collected_at' => 'date',
        'added_at' => 'datetime',
        'source_payload' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
