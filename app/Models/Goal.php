<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'label', 'icon', 'target_amount', 'is_primary', 'sort_order'
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    public $timestamps = false;
}
