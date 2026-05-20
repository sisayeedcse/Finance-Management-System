<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\BalanceService;
use App\Services\PdfService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(string $memberId)
    {
        $member = Member::findOrFail($memberId);
        $wallet = BalanceService::getMemberWallet($memberId);

        return response()->json([
            'member' => $member,
            'wallet' => $wallet,
        ]);
    }

    public function passbook(string $memberId)
    {
        $member = Member::findOrFail($memberId);
        $wallet = BalanceService::getMemberWallet($memberId);

        return response()->json([
            'member' => $member,
            'passbook' => $wallet['passbook'],
        ]);
    }

    public function passpdf(string $memberId)
    {
        return PdfService::generatePassbook($memberId);
    }
}
