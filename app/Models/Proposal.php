<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'date',
        'proposed_by',
        'status',
        'votes_yes',
        'votes_no',
        'created_at',
    ];

    protected $casts = [
        'votes_yes' => 'array',
        'votes_no' => 'array',
    ];
}
