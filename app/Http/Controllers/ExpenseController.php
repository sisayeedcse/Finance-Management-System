<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\ActivityService;
use App\Http\Requests\StoreExpenseRequest;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Expense::orderByDesc('expense_date')->get()]);
    }

    public function store(StoreExpenseRequest $request)
    {
        $expense = Expense::create([
            'amount' => $request->amount,
            'description' => $request->description,
            'expense_date' => $request->expense_date,
            'recorded_by' => $request->user()->id,
        ]);

        ActivityService::log('log_expense', "Logged expense: {$request->amount}", $request->user()->id);
        return response()->json($expense, 201);
    }

    public function update(string $id, Request $request)
    {
        $expense = Expense::findOrFail($id);
        $expense->update($request->only(['amount', 'description', 'expense_date']));
        ActivityService::log('update_expense', "Updated expense {$id}", $request->user()->id);
        return response()->json($expense);
    }

    public function destroy(string $id, Request $request)
    {
        $expense = Expense::findOrFail($id);
        ActivityService::log('delete_expense', "Deleted expense {$id}", $request->user()->id);
        $expense->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
