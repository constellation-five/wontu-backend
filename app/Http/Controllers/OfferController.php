<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinOfferRequest;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Offer;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    /**
     * Validasi apakah seller mencoba memesan di offer sendiri
     */
    private function validateSellerNotBuyer(Offer $offer, string|int $userId): ?JsonResponse
    {
        if ($offer->seller_id === $userId) {
            return response()->json([
                'message' => 'Penjual tidak bisa memesan di penawarannya sendiri.'
            ], 403);
        }
        
        return null;
    }

    /**
     * Update stok item dengan validasi
     * @param Item $item - Item yang akan diupdate
     * @param int $quantityChange - Perubahan quantity (positif = tambah, negatif = kurangi)
     * @param bool $allowNegative - Izinkan hasil negatif (untuk rollback)
     */
    private function updateItemStock(Item $item, int $quantityChange, bool $allowNegative = false): ?JsonResponse
    {
        $newCurrentSlot = $item->current_slot + $quantityChange;

        // Validasi: current_slot tidak boleh negatif (kecuali diizinkan untuk rollback)
        if (!$allowNegative && $newCurrentSlot < 0) {
            return response()->json([
                'message' => "Tidak bisa mengurangi stok item '{$item->item_name}' lebih dari yang sudah dipesan.",
            ], 400);
        }

        // Validasi: current_slot tidak boleh melebihi slot
        if ($newCurrentSlot > $item->slot) {
            return response()->json([
                'message' => "Stok item '{$item->item_name}' tidak cukup.",
                'available' => $item->slot - $item->current_slot
            ], 400);
        }
        
        $item->current_slot = max(0, $newCurrentSlot); // Pastikan tidak negatif
        $item->save();
        
        return null;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $offers = Offer::with(['items', 'seller'])
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

    public function show(Offer $offer) {
        $offer->load(['items', 'seller']);

        return response()->json($offer);
    }

    public function getPaymentMethods(Offer $offer): JsonResponse
    {
        // Get payment methods of the offer's seller
        $paymentMethods = $offer->seller->paymentMethods()->get();

        return response()->json([
            'status' => 'success',
            'data' => $paymentMethods
        ], 200);
    }

    public function placeOrder(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer|exists:items,item_id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $offer, $userId) {
            // Validasi seller tidak bisa jadi buyer
            if ($error = $this->validateSellerNotBuyer($offer, $userId)) {
                return $error;
            }

            $offer->buyers()->syncWithoutDetaching([$userId]);

            // Proses setiap item
            foreach ($validated['items'] as $orderItem) {
                $item = $offer->items()->find($orderItem['item_id']);
                
                if ($item) {
                    if ($error = $this->updateItemStock($item, $orderItem['quantity'])) {
                        return $error;
                    }
                }
            }

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

        return DB::transaction(function () use ($validated, $offer) {
            foreach ($validated['items'] as $orderItem) {
                $item = $offer->items()->find($orderItem['item_id']);
                
                if ($item) {
                    if ($error = $this->updateItemStock($item, $orderItem['quantity_diff'])) {
                        return $error;
                    }
                }
            }

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
            'old_items' => 'required|array',
            'old_items.*.item_id' => 'required|integer|exists:items,item_id',
            'old_items.*.quantity' => 'required|integer|min:0',
            'new_items' => 'required|array',
            'new_items.*.item_id' => 'required|integer|exists:items,item_id',
            'new_items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $offer, $userId) {
            // Validasi seller tidak bisa jadi buyer
            if ($error = $this->validateSellerNotBuyer($offer, $userId)) {
                return $error;
            }

            // Step 1: Kembalikan stock dari order lama (kurangi current_slot)
            foreach ($validated['old_items'] as $oldItem) {
                $item = $offer->items()->find($oldItem['item_id']);
                if ($item && $oldItem['quantity'] > 0) {
                    // Gunakan nilai negatif untuk mengurangi, allowNegative = true untuk rollback
                    $this->updateItemStock($item, -$oldItem['quantity'], true);
                }
            }

            // Step 2: Tambah stock untuk order baru (tambah current_slot)
            foreach ($validated['new_items'] as $newItem) {
                $item = $offer->items()->find($newItem['item_id']);
                
                if ($item) {
                    if ($error = $this->updateItemStock($item, $newItem['quantity'])) {
                        return $error;
                    }
                }
            }

            $offer->buyers()->syncWithoutDetaching([$userId]);

            return response()->json([
                'message' => 'Pesanan berhasil diproses.',
                'offer' => $offer->fresh()->load('items', 'buyers'),
            ], 200);
        });
    }

    public function cancelOrder(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer|exists:items,item_id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $offer, $userId) {
            // Check if user is buyer of this offer
            if (!$offer->buyers()->where('users.user_id', $userId)->exists()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki pesanan di offer ini.'
                ], 404);
            }

            // Step 1: Kembalikan stock dari items yang dipesan (kurangi current_slot)
            foreach ($validated['items'] as $orderItem) {
                $item = $offer->items()->find($orderItem['item_id']);
                
                if ($item) {
                    // Gunakan nilai negatif untuk mengurangi, allowNegative = true untuk rollback
                    $this->updateItemStock($item, -$orderItem['quantity'], true);
                }
            }

            // Step 2: Hapus user dari buyers (detach dari pivot table)
            $offer->buyers()->detach($userId);

            return response()->json([
                'message' => 'Pesanan berhasil dibatalkan dan stok dikembalikan.',
                'offer' => $offer->fresh()->load('items', 'buyers'),
            ], 200);
        });
    }
}
