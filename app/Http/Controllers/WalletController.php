<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Services\PdfService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(string $memberId)
    {
        $this->authorizeMember($memberId);
        return response()->json(BalanceService::getMemberWallet($memberId));
    }

    public function passbook(string $memberId)
    {
        $this->authorizeMember($memberId);

        $wallet = BalanceService::getMemberWallet($memberId);
        $member = Member::findOrFail($memberId);

        return response()->json([
            'member' => $member,
            'wallet' => $wallet,
            'passbook' => $wallet['passbook'] ?? [],
        ]);
    }

    public function passpdf(string $memberId)
    {
        return PdfService::generatePassbook($memberId);
    }

    private function authorizeMember(string $memberId): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        if ($user->id === $memberId) {
            return;
        }

        if (in_array($user->role, ['admin', 'finance'], true)) {
            return;
        }

        abort(403);
    }
}
