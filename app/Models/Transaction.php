<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 'type', 'amount', 'note', 'date', 'created_by', 'paymentForYear', 'paymentForMonth'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
