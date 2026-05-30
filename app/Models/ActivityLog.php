<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $fillable = [
        'source_id', 'action', 'description', 'performed_by', 'performed_by_name',
        'performed_by_email', 'performed_by_role', 'iso', 'ts', 'source_payload'
    ];

    public $timestamps = false;

    protected $casts = [
        'iso' => 'datetime',
        'ts' => 'integer',
        'source_payload' => 'array',
    ];
}
