<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public static function generatePaymentReport(int $month, int $year): \Symfony\Component\HttpFoundation\Response
    {
        $monthLabel = date('F', mktime(0, 0, 0, $month, 1, $year));
        $members = Member::where('status', 'active')->get();
        $payments = Payment::where('month', $month)->where('year', $year)->get()->keyBy('member_id');

        $data = [
            'month_label' => $monthLabel,
            'year' => $year,
            'members' => $members,
            'payments' => $payments,
            'total_due' => $members->sum('monthly_due'),
            'collected' => $payments->where('status', 'paid')->sum('amount'),
        ];

        $pdf = Pdf::loadView('pdfs.payment-report', $data);

        return $pdf->download("SIPR-Payment-{$monthLabel}-{$year}.pdf");
    }

    public static function generatePassbook(string $memberId): \Symfony\Component\HttpFoundation\Response
    {
        $member = Member::findOrFail($memberId);
        $wallet = BalanceService::getMemberWallet($memberId);

        $pdf = Pdf::loadView('pdfs.passbook', compact('member', 'wallet'));

        return $pdf->download("SIPR-Passbook-{$member->name}.pdf");
    }
}
