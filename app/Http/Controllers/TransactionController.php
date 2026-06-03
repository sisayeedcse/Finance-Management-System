<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Payment;
use App\Services\ActivityService;
use App\Http\Requests\StoreTransactionRequest;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('member:id,name');

        if ($request->filled('member_id') && $request->member_id !== 'all') {
            $query->where('member_id', $request->member_id);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $txs = $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'member_id' => $t->member_id,
                    'member_name' => $t->member?->name ?? $t->member_id,
                    'amount' => $t->amount,
                    'date' => $t->date,
                    'note' => $t->note,
                    'created_at' => $t->created_at,
                ];
            });

        return response()->json($txs);
    }

    public function store(StoreTransactionRequest $request)
    {
        $memberId = $request->member_id ?? $request->get('memberUID') ?? null;
        $paymentYear = $request->get('paymentForYear');
        $paymentMonth = $request->get('paymentForMonth');
        $transaction = Transaction::create([
            'member_id' => $memberId,
            'type' => $request->type,
            'amount' => $request->amount,
            'note' => $request->note,
            'date' => $request->date,
            'created_by' => $request->user()->id,
            'paymentForYear' => $paymentYear,
            'paymentForMonth' => $paymentMonth,
        ]);

        if ($request->type === 'deposit' && $memberId) {
            $paymentDate = $request->date ? new \DateTime($request->date) : new \DateTime();
            $paymentMonth = $paymentMonth ?? (int) $paymentDate->format('n');
            $paymentYear = $paymentYear ?? (int) $paymentDate->format('Y');

            Payment::updateOrCreate(
                [
                    'member_id' => $memberId,
                    'month' => $paymentMonth,
                    'year' => $paymentYear,
                ],
                [
                    'amount' => $request->amount,
                    'status' => 'paid',
                    'paid_at' => $request->date,
                    'recorded_by' => $request->user()->id,
                ],
            );
        }

        ActivityService::log('add_transaction', "Added {$request->type} transaction of {$request->amount}", $request->user()->id);

        return response()->json($transaction, 201);
    }

    public function update(string $id, Request $request)
    {
        $transaction = Transaction::findOrFail($id);

        $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'note' => 'nullable|string',
            'date' => 'sometimes|date',
        ]);

        $transaction->update($request->only(['amount', 'note', 'date']));

        if ($transaction->type === 'deposit' && $transaction->member_id) {
            $paymentDate = $transaction->date ? new \DateTime($transaction->date) : new \DateTime();
            $paymentMonth = $transaction->paymentForMonth ?? ((int) $paymentDate->format('n') - 1);
            $paymentYear = $transaction->paymentForYear ?? (int) $paymentDate->format('Y');

            Payment::updateOrCreate(
                [
                    'member_id' => $transaction->member_id,
                    'month' => $paymentMonth,
                    'year' => $paymentYear,
                ],
                [
                    'amount' => $transaction->amount,
                    'status' => 'paid',
                    'paid_at' => $transaction->date,
                ]
            );
        }

        ActivityService::log('update_transaction', "Updated transaction {$id}", $request->user()->id);

        return response()->json($transaction);
    }

    public function destroy(string $id, Request $request)
    {
        $transaction = Transaction::findOrFail($id);
        ActivityService::log('delete_transaction', "Deleted transaction {$id}", $request->user()->id);
        $transaction->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
