<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinOfferRequest;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $offers = Offer::with(['items'])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('merchant_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('item_name', 'LIKE', "%{$search}%");
                    });
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $offers
        ], 200);
    }

    public function store(StoreOfferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $offer = Offer::create([
            'seller_id' => $request->user()->user_id,
            'category' => $validated['category'],
            'merchant_name' => $validated['merchant_name'] ?? '',
            'closing_time' => $validated['closing_time'],
            'arrival_time' => $validated['arrival_time'],
            'has_cod_payment' => $validated['has_cod_payment'] ?? false,
            'is_completed' => false,
        ]);

        if (! empty($validated['items'] ?? [])) {
            $offer->items()->createMany($validated['items']);
        }

        return response()->json([
            'message' => 'Offer created successfully.',
            'offer' => $offer->load('items'),
        ], 201);
    }

    public function join(JoinOfferRequest $request, Offer $offer): JsonResponse
    {
        $user = $request->user();
        $userId = $user->user_id;

        // Check if user is the seller
        if ($offer->seller_id === $userId) {
            return response()->json([
                'message' => 'You cannot join your own offer.',
            ], 403);
        }

        // Attach user to offer if not already joined
        if (!$offer->buyers()->where('users.user_id', $userId)->exists()) {
            $offer->buyers()->attach($userId);
        }

        return response()->json([
            'message' => 'Successfully joined the offer.',
            'offer' => $offer->load('items', 'buyers'),
        ], 200);
    }
}
