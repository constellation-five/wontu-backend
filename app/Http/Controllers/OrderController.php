<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitOrderRequest;
use App\Models\Offer;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    // Buyer pov
   public function showMyOrder(Request $request, $offerId): JsonResponse
    {
        $user = $request->user(); 

        $offerBuyer = DB::table('offer_buyers')
            ->where('offer_id', $offerId)
            ->where('buyer_id', $user->user_id)
            ->first();

        if (! $offerBuyer) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $buyerItems = DB::table('buyer_items')
            ->join('items', 'buyer_items.item_id', '=', 'items.item_id')
            ->where('buyer_items.offer_buyer_id', $offerBuyer->offer_buyer_id)
            ->select('items.item_id', 'items.item_name', 'items.item_price', 'buyer_items.quantity', 'buyer_items.notes', 'items.image_url')
            ->get();

        $totalAmount = $buyerItems->sum(function ($item) {
            return $item->item_price * $item->quantity;
        });

        return response()->json([
            'order_id' => $offerId,
            'customer' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ],
            'order' => [
                'status' => $offerBuyer->status,
                'notes' => '', 
                'total_amount' => (float)$totalAmount,
                'payment_proof_url' => $offerBuyer->payment_proof_url,
            ],
            'items' => $buyerItems->map(function ($item) {
                return [
                    'item_id' => $item->item_id,
                    'item_name' => $item->item_name,
                    'item_price' => (float)$item->item_price,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                    'image' => $item->image_url,
                ];
            }),
        ], 200);
    }

    // Seller pov
    public function showCustomerOrder(Request $request, $offerId, string $buyerId): JsonResponse
    {
        $user = $request->user();

        $offer = DB::table('offers')->where('offer_id', $offerId)->first();
        if (!$offer) return response()->json(['message' => 'Offer not found'], 404);

        $buyer = DB::table('users')->where('user_id', $buyerId)->first();
        if (!$buyer) return response()->json(['message' => 'Buyer not found'], 404);

        if ($offer->seller_id !== $user->user_id) {
            return response()->json(['message' => 'Unauthorized. You do not own this offer.'], 403);
        }

        $offerBuyer = DB::table('offer_buyers')
            ->where('offer_id', $offerId)
            ->where('buyer_id', $buyerId)
            ->first();

        if (! $offerBuyer) {
            return response()->json(['message' => 'Customer order not found'], 404);
        }

        $buyerItems = DB::table('buyer_items')
            ->join('items', 'buyer_items.item_id', '=', 'items.item_id')
            ->where('buyer_items.offer_buyer_id', $offerBuyer->offer_buyer_id)
            ->select('items.item_id', 'items.item_name', 'items.item_price', 'buyer_items.quantity', 'buyer_items.notes', 'items.image_url')
            ->get();

        $totalAmount = $buyerItems->sum(function ($item) {
            return $item->item_price * $item->quantity;
        });

        return response()->json([
            'order_id' => $offer->offer_id,
            'customer' => [
                'name' => $buyer->name,
                'email' => $buyer->email,
                'avatar' => $buyer->avatar,
            ],
            'order' => [
                'status' => $offerBuyer->status,
                'notes' => '', 
                'total_amount' => (float)$totalAmount,
                'payment_proof_url' => $offerBuyer->payment_proof_url,
            ],
            'items' => $buyerItems->map(function ($item) {
                return [
                    'item_id' => $item->item_id,
                    'item_name' => $item->item_name,
                    'item_price' => (float)$item->item_price,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                    'image' => $item->image_url, 
                ];
            }),
        ], 200);
    }

    public function confirmPayment(Request $request, $offerId, $buyerId): JsonResponse
    {
        $user = $request->user();

        $offer = DB::table('offers')->where('offer_id', $offerId)->first();
        
        if (!$offer) {
            return response()->json(['message' => 'Offer not found'], 404);
        }

        if ($offer->seller_id !== $user->user_id) {
            return response()->json(['message' => 'Unauthorized. You cannot confirm this payment.'], 403);
        }

        $updatedRows = DB::table('offer_buyers')
            ->where('offer_id', $offerId)
            ->where('buyer_id', $buyerId)
            ->update([
                'status' => 'confirmed',
                'updated_at' => now()
            ]);

        if ($updatedRows === 0) {
            return response()->json(['message' => 'Customer order not found.'], 404);
        }

        return response()->json(['message' => 'Payment confirmed successfully!'], 200);
    }
}
