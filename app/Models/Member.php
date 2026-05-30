<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as AuthUser;

class Member extends AuthUser
{
    use HasApiTokens, HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'email', 'phone', 'title', 'role',
        'locked', 'status', 'google_uid', 'google_email',
        'monthly_due', 'password', 'photo', 'gmail', 'wa_link',
        'address', 'emoji', 'permissions', 'registered_at',
        'restored_at', 'source_payload',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'locked' => 'boolean',
        'monthly_due' => 'decimal:2',
        'password' => 'hashed',
        'permissions' => 'array',
        'registered_at' => 'datetime',
        'restored_at' => 'datetime',
        'source_payload' => 'array',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'member_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'member_id', 'id');
    }
}
