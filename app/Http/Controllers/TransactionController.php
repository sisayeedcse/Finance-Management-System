<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
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
        $transaction = Transaction::create([
            'member_id' => $request->member_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'note' => $request->note,
            'date' => $request->date,
            'created_by' => $request->user()->id,
        ]);

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
