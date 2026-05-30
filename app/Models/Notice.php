<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id', 'type', 'title', 'body', 'pinned', 'posted_by', 'source_payload'
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'source_payload' => 'array',
    ];
}
