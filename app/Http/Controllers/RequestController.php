<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; 
use App\Models\Request as RequestModel;
use App\Models\RequestVoter;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    /**
     * Requests only show up for users within this many meters of the request's location.
     * (Disamakan dengan OfferController atau sesuaikan kebutuhan, misal 2000 meter)
     */
    private const NEARBY_RADIUS_METERS = 200;

    // GET ALL REQUESTS
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $validated = $request->validate([
            'lat' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:lat'],
        ]);

        $requests = RequestModel::with(['requester'])
            ->withCoordinates()
            ->when($search, function ($query, $search) {
                return $query->where('item_name', 'LIKE', "%{$search}%");
            })
            ->when(isset($validated['lat'], $validated['lng']), function ($query) use ($validated) {
                return $query->nearby($validated['lat'], $validated['lng'], self::NEARBY_RADIUS_METERS);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $userId = Auth::id();

        $requests->map(function ($req) use ($userId) {
            if ($userId) {
                $req->has_voted = $req->voters()->where('users.user_id', $userId)->exists();
            } else {
                $req->has_voted = false; 
            }
            return $req;
        });

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ], 200);
    }

    // CREATE
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:64',
            'category' => 'required|in:food,other',
            'arrival_time' => 'required|date',
            'location_label' => 'nullable|string|max:255',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
        ]);

        $newRequest = RequestModel::create([
            'requester_id' => Auth::user()->user_id,
            'item_name' => $validated['item_name'],
            'category' => $validated['category'],
            'arrival_time' => $validated['arrival_time'],
            'location_label' => $validated['location_label'] ?? null,
            // Sama seperti di OfferController, cek jika lat/lng dikirim
            'location' => isset($validated['location_lat'], $validated['location_lng']) 
                ? RequestModel::makePoint($validated['location_lat'], $validated['location_lng']) 
                : null,
            'total_votes' => 0
        ]);

        return response()->json([
            'message' => 'Successfully created', 
            'data' => $newRequest
        ], 201);
    }

    // UPDATE
    public function update(Request $request, $id): JsonResponse
    {
        $requestItem = RequestModel::findOrFail($id);

        if ($requestItem->requester_id !== Auth::user()->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'item_name' => 'sometimes|required|string|max:64',
            'category' => 'sometimes|required|in:food,other',
            'arrival_time' => 'sometimes|required|date',
            'location_label' => 'nullable|string|max:255',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
        ]);

        if (isset($validated['item_name'])) $requestItem->item_name = $validated['item_name'];
        if (isset($validated['category'])) $requestItem->category = $validated['category'];
        if (isset($validated['arrival_time'])) $requestItem->arrival_time = $validated['arrival_time'];
        if (array_key_exists('location_label', $validated)) $requestItem->location_label = $validated['location_label'];

        if (isset($validated['location_lat'], $validated['location_lng'])) {
            $requestItem->location = RequestModel::makePoint($validated['location_lat'], $validated['location_lng']);
        }

        $requestItem->save();

        return response()->json([
            'message' => 'Successfully updated', 
            'data' => $requestItem
        ], 200);
    }

    // DELETE
    public function destroy($id): JsonResponse
    {
        $requestItem = RequestModel::findOrFail($id);

        if ($requestItem->requester_id !== Auth::user()->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requestItem->delete();

        return response()->json(['message' => 'Successfully removed'], 200);
    }

    // TOGGLE VOTE
    public function toggleVote($id): JsonResponse
    {
        $userId = Auth::user()->user_id;
        $requestItem = RequestModel::findOrFail($id);

        // Coba hapus langsung menggunakan Query Builder
        $deleted = RequestVoter::where('user_id', $userId)
                               ->where('request_id', $id)
                               ->delete();

        if ($deleted) {
            // Jika berhasil dihapus berarti sedang Unlove / Cancel Vote
            $requestItem->decrement('total_votes');
            $message = 'Cancel Vote';
        } else {
            // Jika tidak ada yang dihapus berarti sedang Love / Vote
            RequestVoter::create([
                'request_id' => $id,
                'user_id' => $userId
            ]);
            $requestItem->increment('total_votes');
            $message = 'Add Vote';
        }

        return response()->json([
            'message' => $message, 
            'total_votes' => $requestItem->total_votes
        ], 200);
    }
}