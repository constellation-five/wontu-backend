<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use App\Models\Request as RequestModel;
use App\Models\RequestVoter;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    // CREATE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:64',
            'category' => 'required|in:food,other',
            'arrival_time' => 'required|date',
        ]);

        $newRequest = RequestModel::create([
            'requester_id' => Auth::user()->user_id,
            'item_name' => $validated['item_name'],
            'category' => $validated['category'],
            'arrival_time' => $validated['arrival_time'],
            'total_votes' => 0
        ]);

        return response()->json(['message' => 'Successfully created', 'data' => $newRequest]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $wontuRequest = RequestModel::findOrFail($id);

        if ($wontuRequest->requester_id !== Auth::user()->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $wontuRequest->update($request->only(['item_name', 'category', 'arrival_time']));

        return response()->json(['message' => 'Successfully updated', 'data' => $wontuRequest]);
    }

    // DELETE
    public function destroy($id)
    {
        $wontuRequest = RequestModel::findOrFail($id);

        if ($wontuRequest->requester_id !== Auth::user()->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $wontuRequest->delete();

        return response()->json(['message' => 'Successfully removed']);
    }

    // TOGGLE VOTE
    public function toggleVote($id)
    {
        $userId = Auth::user()->user_id;
        $wontuRequest = RequestModel::findOrFail($id);

        $existingVote = RequestVoter::where('user_id', $userId)
                                    ->where('request_id', $id)
                                    ->first();

        if ($existingVote) {
            // Unlove / Cancel Vote
            $existingVote->delete();
            $wontuRequest->decrement('total_votes');
            $message = 'Cancel Vote';
        } else {
            // Love / Vote
            RequestVoter::create([
                'request_id' => $id,
                'user_id' => $userId
            ]);
            $wontuRequest->increment('total_votes');
            $message = 'Add Vote';
        }

        return response()->json([
            'message' => $message, 
            'total_votes' => $wontuRequest->total_votes
        ]);
    }
}