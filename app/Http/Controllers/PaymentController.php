<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Member;
use App\Services\ActivityService;
use App\Services\PdfService;
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $members = Member::where('status', 'active')->get();
        $payments = Payment::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('member_id');

        $list = $members->map(function ($m) use ($payments) {
            $p = $payments[$m->id] ?? null;

            return [
                'member_id' => $m->id,
                'name' => $m->name,
                'status' => $p?->status ?? 'pending',
                'amount_due' => $m->monthly_due,
                'amount' => $p?->amount ?? 0,
                'paid_at' => $p?->paid_at,
                'payment_id' => $p?->id,
            ];
        })->values();

        $paidCount = $payments->where('status', 'paid')->count();
        $totalDue = $members->sum('monthly_due');
        $collected = $payments->where('status', 'paid')->sum('amount');

        return response()->json([
            'list' => $list,
            'paid_count' => $paidCount,
            'pending_count' => $members->count() - $paidCount,
            'total' => $members->count(),
            'collected' => $collected,
            'rate' => $totalDue > 0 ? round(($collected / $totalDue) * 100) : 0,
            'month' => (int) $month,
            'year' => (int) $year,
        ]);
    }

    public function store(StorePaymentRequest $request)
    {

        $attributes = [
            'member_id' => $request->member_id,
            'month' => $request->month,
            'year' => $request->year,
        ];

        $existing = Payment::where($attributes)->first();

        $values = [
            'amount' => $request->amount,
            'paid_at' => ($request->status ?? 'paid') === 'paid' ? ($request->paid_at ?? now()) : null,
            'status' => $request->status ?? 'paid',
            'recorded_by' => $request->user()->id,
        ];

        $payment = Payment::updateOrCreate($attributes, $values);

        // Logging: record when newly created or when status moved to paid; otherwise log update
        if (!$existing) {
            ActivityService::log('record_payment', "Recorded payment for member {$payment->member_id}", $request->user()->id);
        } else {
            if ($existing->status !== 'paid' && $payment->status === 'paid') {
                ActivityService::log('record_payment', "Recorded payment for member {$payment->member_id}", $request->user()->id);
            } else {
                ActivityService::log('update_payment', "Updated payment {$payment->id}", $request->user()->id);
            }
        }

        return response()->json($payment, 201);
    }

    public function destroy(string $id, Request $request)
    {
        $payment = Payment::findOrFail($id);
        ActivityService::log('delete_payment', "Deleted payment {$id}", $request->user()->id);
        $payment->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function exportCsv(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $monthLabel = date('F', mktime(0, 0, 0, $month, 1, $year));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=SIPR-Payments-{$monthLabel}-{$year}.csv",
        ];

        $members = Member::where('status', 'active')->get();
        $payments = Payment::where('month', $month)->where('year', $year)->get()->keyBy('member_id');

        $callback = function () use ($members, $payments, $monthLabel, $year) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SIPR Group - Monthly Payments', $monthLabel, $year]);
            fputcsv($out, ['Member', 'Status', 'Amount Due', 'Amount Paid', 'Date Paid']);
            foreach ($members as $m) {
                $p = $payments[$m->id] ?? null;
                fputcsv($out, [
                    $m->name,
                    $p?->status ?? 'pending',
                    $m->monthly_due,
                    $p?->amount ?? 0,
                    $p?->paid_at ?? '–',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function whatsappText(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $monthLabel = date('F', mktime(0, 0, 0, $month, 1));

        $members = Member::where('status', 'active')->get();
        $payments = Payment::where('month', $month)->where('year', $year)->get()->keyBy('member_id');

        $lines = ["*SIPR Group — {$monthLabel} {$year} Payment Status*\n"];
        $paid = 0;
        $pending = 0;

        foreach ($members as $m) {
            $p = $payments[$m->id] ?? null;
            $status = ($p && $p->status === 'paid') ? '✅ Paid' : '❌ Pending';
            if ($p && $p->status === 'paid') {
                $paid++;
            } else {
                $pending++;
            }
            $lines[] = "{$status} — {$m->name} (BDT {$m->monthly_due})";
        }

        $lines[] = "\nPaid: {$paid}/{$members->count()} · Pending: {$pending}";
        $lines[] = "Total Collected: BDT " . $payments->where('status', 'paid')->sum('amount');

        return response()->json(['text' => implode("\n", $lines)]);
    }

    public function exportPdf(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $monthLabel = date('F', mktime(0, 0, 0, $month, 1, $year));

        return PdfService::generatePaymentReport($month, $year);
    }
}
