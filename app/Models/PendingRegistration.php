<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'invite_code',
        'password',
        'member_id',
        'approved_role',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];
}
