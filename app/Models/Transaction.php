<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id', 'member_id', 'member_name', 'member_email', 'member_uid',
        'type', 'amount', 'note', 'date', 'created_by', 'paymentForYear',
        'paymentForMonth', 'source_payload'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'source_payload' => 'array',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
