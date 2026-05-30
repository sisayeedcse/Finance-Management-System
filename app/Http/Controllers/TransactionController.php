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
        $query = Transaction::query();

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json(['data' => $query->orderByDesc('date')->get()]);
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
