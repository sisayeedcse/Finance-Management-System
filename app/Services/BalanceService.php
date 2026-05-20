<?php

namespace App\Services;

use App\Models\Transaction;

class BalanceService
{
    public static function getFundBalance(): float
    {
        return Transaction::all()->sum(function ($t) {
            return in_array($t->type, ['deposit', 'profit']) ? $t->amount : -$t->amount;
        });
    }

    public static function getMemberWallet(string $memberId): array
    {
        $txs = Transaction::where('member_id', $memberId)->get();
        $deposited = $txs->whereIn('type', ['deposit', 'profit'])->sum('amount');
        $fines = $txs->where('type', 'fine')->sum('amount');
        return [
            'deposited' => $deposited,
            'fines' => $fines,
            'balance' => $deposited - $fines,
            'passbook' => $txs->sortByDesc('date')->values()->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'date' => $t->date,
                    'note' => $t->note,
                ];
            })->all(),
        ];
    }
}
