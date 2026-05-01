<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitOrderRequest;
use App\Models\Offer;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function submitOrder(SubmitOrderRequest $request, Offer $offer): JsonResponse
    {
        $user = $request->user();
        $userId = $user->user_id;
        $validated = $request->validated();

        if ($offer->seller_id === $userId) {
            return response()->json([
                'message' => 'You cannot submit an order for your own offer.',
            ], 403);
        }

        $pivotData = [
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
            'payment_proof_url' => $validated['payment_proof_url'] ?? null,
            'total_amount' => $validated['total_amount'] ?? 0,
        ];

        $calculatedTotal = collect($validated['items'])
            ->sum(fn ($item) => $item['item_price'] * $item['quantity']);

        $pivotData['total_amount'] = $validated['total_amount'] ?? $calculatedTotal;

        if ($offer->buyers()->where('users.user_id', $userId)->exists()) {
            $offer->buyers()->updateExistingPivot($userId, $pivotData);
        } else {
            $offer->buyers()->attach($userId, $pivotData);
        }

        OrderItem::where('offer_id', $offer->offer_id)
            ->where('user_id', $userId)
            ->delete();

        $orderItems = array_map(function ($item) use ($offer, $userId) {
            return [
                'offer_id' => $offer->offer_id,
                'user_id' => $userId,
                'item_name' => $item['item_name'],
                'item_price' => $item['item_price'],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $validated['items']);

        OrderItem::insert($orderItems);

        return response()->json([
            'message' => 'Order submitted successfully.',
            'offer' => $offer->load('items', 'buyers'),
        ], 201);
    }

    public function showOrderDetails(Request $request, Offer $offer, User $buyer): JsonResponse
    {
        $user = $request->user();

        if ($offer->seller_id !== $user->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $buyerOrder = $offer->buyers()
            ->where('users.user_id', $buyer->user_id)
            ->first();

        if (! $buyerOrder) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $pivot = $buyerOrder->pivot;

        $buyerOrderItems = OrderItem::where('offer_id', $offer->offer_id)
            ->where('user_id', $buyer->user_id)
            ->get();

        return response()->json([
            'customer' => [
                'name' => $buyer->name,
                'email' => $buyer->email,
                'avatar' => $buyer->avatar,
            ],
            'order' => [
                'status' => $pivot->status ?? 'pending',
                'notes' => $pivot->notes ?? '',
                'total_amount' => $pivot->total_amount ?? 0,
                'payment_proof_url' => $pivot->payment_proof_url ?? null,
            ],
            'items' => $buyerOrderItems->map(function ($orderItem) {
                return [
                    'item_name' => $orderItem->item_name,
                    'item_price' => $orderItem->item_price,
                    'quantity' => $orderItem->quantity,
                    'subtotal' => $orderItem->item_price * $orderItem->quantity,
                    'item_notes' => $orderItem->notes,
                ];
            }),
        ], 200);
    }
}
