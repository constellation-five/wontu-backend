<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfferRequest;
use App\Models\Item;
use App\Models\Offer;
use App\Models\OfferBuyer;
use App\Notifications\BuyerJoinedNotification;
use App\Notifications\OfferCompletedNotification;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderUpdatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    /**
     * Offers only show up for buyers within this many meters of the offer's location.
     */
    private const NEARBY_RADIUS_METERS = 200;

    /**
     * Validasi apakah seller mencoba memesan di offer sendiri
     */
    private function validateSellerNotBuyer(Offer $offer, string|int $userId): ?JsonResponse
    {
        if ($offer->seller_id === $userId) {
            return response()->json([
                'message' => 'Penjual tidak bisa memesan di penawarannya sendiri.',
            ], 403);
        }

        return null;
    }

    /**
     * Update stok item dengan validasi
     *
     * @param  Item  $item  - Item yang akan diupdate
     * @param  int  $quantityChange  - Perubahan quantity (positif = tambah, negatif = kurangi)
     * @param  bool  $allowNegative  - Izinkan hasil negatif (untuk rollback)
     */
    private function updateItemStock(Item $item, int $quantityChange, bool $allowNegative = false): ?JsonResponse
    {
        $newCurrentSlot = $item->current_slot + $quantityChange;

        // Validasi: current_slot tidak boleh negatif (kecuali diizinkan untuk rollback)
        if (! $allowNegative && $newCurrentSlot < 0) {
            return response()->json([
                'message' => "Tidak bisa mengurangi stok item '{$item->item_name}' lebih dari yang sudah dipesan.",
            ], 400);
        }

        // Validasi: current_slot tidak boleh melebihi slot
        if ($newCurrentSlot > $item->slot) {
            return response()->json([
                'message' => "Stok item '{$item->item_name}' tidak cukup.",
                'available' => $item->slot - $item->current_slot,
            ], 400);
        }

        $item->current_slot = max(0, $newCurrentSlot); // Pastikan tidak negatif
        $item->save();

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $validated = $request->validate([
            'lat' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:lat'],
        ]);

        $offers = Offer::with(['items', 'seller'])
            ->withCoordinates()
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('merchant_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('items', function ($itemQuery) use ($search) {
                            $itemQuery->where('item_name', 'LIKE', "%{$search}%");
                        });
                });
            })
            ->when(isset($validated['lat'], $validated['lng']), function ($query) use ($validated) {
                return $query->nearby($validated['lat'], $validated['lng'], self::NEARBY_RADIUS_METERS);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $offers,
        ], 200);
    }

    public function store(StoreOfferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $offer = Offer::create([
            'seller_id' => $request->user()->user_id,
            'category' => $validated['category'],
            'merchant_name' => $validated['merchant_name'] ?? '',
            'location_label' => $validated['location_label'] ?? null,
            'location' => Offer::makePoint($validated['location_lat'], $validated['location_lng']),
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

    public function show(Offer $offer)
    {
        $offer = Offer::withCoordinates()->with(['items', 'seller'])->findOrFail($offer->offer_id);

        return response()->json($offer);
    }

    /**
     * List all of the authenticated user's orders, across every offer.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        $orders = OfferBuyer::where('buyer_id', $userId)
            ->with(['offer', 'items.item'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders->map(fn ($offerBuyer) => [
                'offer_id' => $offerBuyer->offer_id,
                'merchant_name' => $offerBuyer->offer->merchant_name,
                'status' => $offerBuyer->status,
                'is_verified' => $offerBuyer->is_verified,
                'payment_proof_url' => $offerBuyer->payment_proof_url,
                'joined_at' => $offerBuyer->created_at,
                'payment_submitted_at' => $offerBuyer->payment_submitted_at,
                'verified_at' => $offerBuyer->verified_at,
                'created_at' => $offerBuyer->created_at,
                'items' => $offerBuyer->items->map(fn ($buyerItem) => [
                    'item' => $buyerItem->item,
                    'quantity' => $buyerItem->quantity,
                    'notes' => $buyerItem->notes,
                ]),
            ]),
        ], 200);
    }

    /**
     * Get the authenticated user's order (if any) for this offer.
     */
    public function myOrder(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;

        $offerBuyer = OfferBuyer::where('offer_id', $offer->offer_id)
            ->where('buyer_id', $userId)
            ->with('items.item')
            ->first();

        if (! $offerBuyer) {
            return response()->json([
                'status' => 'success',
                'data' => null,
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'status' => $offerBuyer->status,
                'is_verified' => $offerBuyer->is_verified,
                'payment_proof_url' => $offerBuyer->payment_proof_url,
                'joined_at' => $offerBuyer->created_at,
                'payment_submitted_at' => $offerBuyer->payment_submitted_at,
                'verified_at' => $offerBuyer->verified_at,
                'items' => $offerBuyer->items->map(fn ($buyerItem) => [
                    'item' => $buyerItem->item,
                    'quantity' => $buyerItem->quantity,
                    'notes' => $buyerItem->notes,
                ]),
            ],
        ], 200);
    }

    /**
     * Seller marks the offer as actually closed (distinct from closing_time,
     * which is just the planned schedule).
     */
    public function close(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->seller_id !== $request->user()->user_id) {
            return response()->json([
                'message' => 'Hanya penjual yang bisa menutup offer ini.',
            ], 403);
        }

        if ($offer->closed_at) {
            return response()->json([
                'message' => 'Offer sudah ditutup.',
            ], 409);
        }

        $offer->closed_at = now();
        $offer->save();

        return response()->json([
            'message' => 'Offer berhasil ditutup.',
            'offer' => $offer->fresh(),
        ], 200);
    }

    /**
     * Seller marks the offer's items as actually arrived (distinct from
     * arrival_time, which is just the planned schedule).
     */
    public function markArrived(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->seller_id !== $request->user()->user_id) {
            return response()->json([
                'message' => 'Hanya penjual yang bisa menandai offer ini sebagai tiba.',
            ], 403);
        }

        if ($offer->arrived_at) {
            return response()->json([
                'message' => 'Offer sudah ditandai sebagai tiba.',
            ], 409);
        }

        $offer->arrived_at = now();
        $offer->save();

        return response()->json([
            'message' => 'Offer berhasil ditandai sebagai tiba.',
            'offer' => $offer->fresh(),
        ], 200);
    }

    /**
     * Records that the buyer submitted proof of payment for their order.
     * The actual file upload is handled elsewhere; this just records the
     * moment submission happened (and stores the resulting URL, once that
     * upload feature exists to provide one).
     */
    public function submitPayment(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;
        $validated = $request->validate([
            'payment_proof_url' => 'nullable|string|max:256',
        ]);

        $offerBuyer = OfferBuyer::where('offer_id', $offer->offer_id)
            ->where('buyer_id', $userId)
            ->first();

        if (! $offerBuyer) {
            return response()->json([
                'message' => 'Anda tidak memiliki pesanan di offer ini.',
            ], 404);
        }

        $offerBuyer->payment_submitted_at = now();
        if (! empty($validated['payment_proof_url'])) {
            $offerBuyer->payment_proof_url = $validated['payment_proof_url'];
        }
        $offerBuyer->save();

        return response()->json([
            'message' => 'Bukti pembayaran berhasil dikirim.',
            'offer_buyer' => $offerBuyer->fresh(),
        ], 200);
    }

    /**
     * Buyer expresses intent to join an offer, before placing any items.
     */
    public function join(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;

        if ($offer->seller_id === $userId) {
            return response()->json([
                'message' => 'You cannot join your own offer.',
            ], 403);
        }

        $existing = OfferBuyer::where('offer_id', $offer->offer_id)
            ->where('buyer_id', $userId)
            ->first();

        if (! $existing) {
            OfferBuyer::create([
                'offer_id' => $offer->offer_id,
                'buyer_id' => $userId,
                'status' => 'pending',
            ]);

            $offer->seller->notify(new BuyerJoinedNotification($request->user(), $offer));
        }

        return response()->json([
            'message' => 'Berhasil bergabung dengan offer.',
            'offer' => $offer->fresh()->load('items', 'buyers'),
        ], 200);
    }

    /**
     * Seller marks the offer as complete, notifying all joined buyers.
     */
    public function complete(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->seller_id !== $request->user()->user_id) {
            return response()->json([
                'message' => 'Hanya penjual yang bisa menyelesaikan offer ini.',
            ], 403);
        }

        if ($offer->is_completed) {
            return response()->json([
                'message' => 'Offer sudah selesai.',
            ], 409);
        }

        $offer->is_completed = true;
        $offer->save();

        foreach ($offer->buyers as $buyer) {
            $buyer->notify(new OfferCompletedNotification($offer));
        }

        return response()->json([
            'message' => 'Offer berhasil diselesaikan.',
            'offer' => $offer->fresh(),
        ], 200);
    }

    public function placeOrder(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer|exists:items,item_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $offer, $userId, $request) {
            // Validasi seller tidak bisa jadi buyer
            if ($error = $this->validateSellerNotBuyer($offer, $userId)) {
                return $error;
            }

            $existing = OfferBuyer::where('offer_id', $offer->offer_id)
                ->where('buyer_id', $userId)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'Anda sudah memiliki pesanan di offer ini.',
                ], 409);
            }

            // Proses setiap item
            foreach ($validated['items'] as $orderItem) {
                $item = $offer->items()->find($orderItem['item_id']);

                if ($item) {
                    if ($error = $this->updateItemStock($item, $orderItem['quantity'])) {
                        return $error;
                    }
                }
            }

            $offerBuyer = OfferBuyer::create([
                'offer_id' => $offer->offer_id,
                'buyer_id' => $userId,
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $orderItem) {
                $offerBuyer->items()->create([
                    'item_id' => $orderItem['item_id'],
                    'quantity' => $orderItem['quantity'],
                    'notes' => $orderItem['notes'] ?? null,
                ]);
            }
            $offer->seller->notify(new OrderPlacedNotification($request->user(), $offer));

            return response()->json([
                'message' => 'Pesanan berhasil diproses dan Anda telah bergabung.',
                'offer' => $offer->load('items', 'buyers'),
            ], 200);
        });
    }

    public function updateOrder(Request $request, Offer $offer): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer|exists:items,item_id',
            'items.*.quantity_diff' => 'required|integer',
        ]);

        return DB::transaction(function () use ($validated, $offer, $request) {
            foreach ($validated['items'] as $orderItem) {
                $item = $offer->items()->find($orderItem['item_id']);

                if ($item) {
                    if ($error = $this->updateItemStock($item, $orderItem['quantity_diff'])) {
                        return $error;
                    }
                }
            }

            $offer->seller->notify(new OrderUpdatedNotification($request->user(), $offer));

            return response()->json([
                'message' => 'Pesanan berhasil diupdate.',
                'offer' => $offer->fresh()->load('items'),
            ], 200);
        });
    }

    public function replaceOrder(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer|exists:items,item_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $offer, $userId, $request) {
            // Validasi seller tidak bisa jadi buyer
            if ($error = $this->validateSellerNotBuyer($offer, $userId)) {
                return $error;
            }

            $offerBuyer = OfferBuyer::where('offer_id', $offer->offer_id)
                ->where('buyer_id', $userId)
                ->with('items')
                ->first();

            if (! $offerBuyer) {
                return response()->json([
                    'message' => 'Anda tidak memiliki pesanan di offer ini.',
                ], 404);
            }

            // Step 1: Kembalikan stock dari order lama (kurangi current_slot),
            // menggunakan data pesanan yang tersimpan sebagai sumber kebenaran.
            foreach ($offerBuyer->items as $oldItem) {
                $item = $offer->items()->find($oldItem->item_id);
                if ($item) {
                    // Gunakan nilai negatif untuk mengurangi, allowNegative = true untuk rollback
                    $this->updateItemStock($item, -$oldItem->quantity, true);
                }
            }

            // Step 2: Tambah stock untuk order baru (tambah current_slot)
            foreach ($validated['items'] as $newItem) {
                $item = $offer->items()->find($newItem['item_id']);

                if ($item) {
                    if ($error = $this->updateItemStock($item, $newItem['quantity'])) {
                        return $error;
                    }
                }
            }

            // Ganti daftar item pesanan dengan yang baru
            $offerBuyer->items()->delete();
            foreach ($validated['items'] as $newItem) {
                $offerBuyer->items()->create([
                    'item_id' => $newItem['item_id'],
                    'quantity' => $newItem['quantity'],
                    'notes' => $newItem['notes'] ?? null,
                ]);
            }

            $offer->seller->notify(new OrderUpdatedNotification($request->user(), $offer));

            return response()->json([
                'message' => 'Pesanan berhasil diproses.',
                'offer' => $offer->fresh()->load('items', 'buyers'),
            ], 200);
        });
    }

    public function cancelOrder(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;

        return DB::transaction(function () use ($offer, $userId, $request) {
            $offerBuyer = OfferBuyer::where('offer_id', $offer->offer_id)
                ->where('buyer_id', $userId)
                ->with('items')
                ->first();

            if (! $offerBuyer) {
                return response()->json([
                    'message' => 'Anda tidak memiliki pesanan di offer ini.',
                ], 404);
            }

            // Kembalikan stock dari items yang dipesan (kurangi current_slot),
            // menggunakan data pesanan yang tersimpan sebagai sumber kebenaran.
            foreach ($offerBuyer->items as $orderItem) {
                $item = $offer->items()->find($orderItem->item_id);

                if ($item) {
                    // Gunakan nilai negatif untuk mengurangi, allowNegative = true untuk rollback
                    $this->updateItemStock($item, -$orderItem->quantity, true);
                }
            }

            // Hapus pesanan (buyer_items ikut terhapus lewat cascade)
            $offerBuyer->delete();

            $offer->seller->notify(new OrderCancelledNotification($request->user(), $offer));

            return response()->json([
                'message' => 'Pesanan berhasil dibatalkan dan stok dikembalikan.',
                'offer' => $offer->fresh()->load('items', 'buyers'),
            ], 200);
        });
    }
}
