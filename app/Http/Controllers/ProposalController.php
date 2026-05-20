<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Services\ActivityService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Proposal::orderByDesc('created_at')->get()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:300',
            'description' => 'required|string',
        ]);

        $proposal = Proposal::create([
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount ?: null,
            'date' => $request->date ?: null,
            'proposed_by' => $request->user() ? $request->user()->name : null,
            'status' => 'active',
            'votes_yes' => [],
            'votes_no' => [],
            'created_at' => now(),
        ]);

        ActivityService::log('submit_proposal', "Submitted proposal: {$proposal->title}", $request->user()->id ?? null);
        return response()->json($proposal, 201);
    }

    public function vote(Request $request, $id)
    {
        $request->validate(['direction' => 'required|in:yes,no']);
        $proposal = Proposal::findOrFail($id);
        $email = $request->user() ? $request->user()->email : null;
        if (!$email) return response()->json(['error' => 'Unauthorized'], 401);

        $yes = $proposal->votes_yes ?: [];
        $no = $proposal->votes_no ?: [];

        if ($request->direction === 'yes') {
            if (in_array($email, $yes)) return response()->json($proposal);
            $yes[] = $email;
            $no = array_values(array_filter($no, fn($x) => $x !== $email));
        } else {
            if (in_array($email, $no)) return response()->json($proposal);
            $no[] = $email;
            $yes = array_values(array_filter($yes, fn($x) => $x !== $email));
        }

        $proposal->votes_yes = $yes;
        $proposal->votes_no = $no;
        $proposal->save();

        ActivityService::log('vote_proposal', "Vote {$request->direction} on {$proposal->title}", $request->user()->id);
        return response()->json($proposal);
    }

    public function update(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);
        $proposal->status = $request->status ?: $proposal->status;
        $proposal->save();
        ActivityService::log('update_proposal', "Proposal {$proposal->id} status set to {$proposal->status}", $request->user()->id);
        return response()->json($proposal);
    }
}
