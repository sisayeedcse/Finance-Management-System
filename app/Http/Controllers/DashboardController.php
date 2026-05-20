<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Project;
use App\Services\BalanceService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $txs = Transaction::all();
        $balance = BalanceService::getFundBalance();

        $month = now()->month;
        $year = now()->year;
        $members = Member::where('status', 'active')->get();

        $existingPayments = Payment::where('month', $month)->where('year', $year)
                                   ->pluck('status', 'member_id');

        $paymentList = $members->map(function ($m) use ($existingPayments) {
            return [
                'member_id' => $m->id,
                'name' => $m->name,
                'status' => $existingPayments[$m->id] ?? 'pending',
                'amount_due' => $m->monthly_due,
                'avatar' => $this->getInitials($m->name),
            ];
        });

        $projects = Project::where('status', 'active')->get();

        return response()->json([
            'balance' => $balance,
            'dep' => $txs->where('type', 'deposit')->sum('amount'),
            'inv' => $txs->where('type', 'invest')->sum('amount'),
            'profit' => $txs->where('type', 'profit')->sum('amount'),
            'exp' => $txs->where('type', 'expense')->sum('amount'),
            'payment_month' => $month,
            'payment_year' => $year,
            'payment_list' => $paymentList,
            'paid_count' => $paymentList->where('status', 'paid')->count(),
            'pending_count' => $paymentList->where('status', 'pending')->count(),
            'total_members' => $members->count(),
            'wallet_total' => $balance,
            'active_projects' => $projects->count(),
            'projects' => $projects->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'pl' => $p->returned - $p->capital,
            ]),
            'goal_target' => 50000,
            'goal_percent' => round(($balance / 50000) * 100, 1),
        ]);
    }

    private function getInitials($name)
    {
        return strtoupper(preg_replace('/[^A-Z]/', '', $name)) ?: 'X';
    }
}
