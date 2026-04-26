<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfferRequest;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
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
        ]);

        if (! empty($validated['items'] ?? [])) {
            $offer->items()->createMany($validated['items']);
        }

        return response()->json([
            'message' => 'Offer created successfully.',
            'offer' => $offer->load('items'),
        ], 201);
    }
}
