<?php

namespace App\Http\Controllers;

use App\Events\OfferUpdated;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Item;
use App\Models\Offer;
use App\Models\OfferBuyer;
use App\Models\Rating;
use App\Notifications\BuyerJoinedNotification;
use App\Notifications\BuyerRemovedFromOfferNotification;
use App\Notifications\FollowingUserNewOfferNotification;
use App\Notifications\ItemAdjustedNotification;
use App\Notifications\ItemsArrivedNotification;
use App\Notifications\OfferClosedNotification;
use App\Notifications\OfferCompletedNotification;
use App\Notifications\OfferCreatedFromLikedRequestNotification;
use App\Notifications\OfferDeletedNotification;
use App\Notifications\OfferEditedNotification;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderUpdatedNotification;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PaymentProofUploadedNotification;
use App\Services\ChatService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OfferController extends Controller
{
    /**
     * Offers only show up for buyers within this many meters of the offer's location.
     */
    private const NEARBY_RADIUS_METERS = 200;

    public function __construct(private readonly ChatService $chatService) {}

    /**
     * Validasi apakah seller mencoba memesan di offer sendiri
     */
    private function validateSellerNotBuyer(Offer $offer, string|int $userId): ?JsonResponse
    {
        if ($offer->seller_id === $userId) {
            return response()->json([
                'message' => __('The seller cannot order from their own offer.'),
            ], 403);
        }

        return null;
    }

    private function validateOfferOpen(Offer $offer): ?JsonResponse
    {
        if ($offer->closed_at !== null) {
            return response()->json([
                'message' => __('This offer is closed and does not accept new orders.'),
            ], 409);
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
                'message' => __('Cannot reduce stock of :item_name more than already ordered.', ['item_name' => $item->item_name]),
            ], 400);
        }

        // Validasi: current_slot tidak boleh melebihi slot
        if ($newCurrentSlot > $item->slot) {
            return response()->json([
                'message' => __('Not enough :item_name in stock.', ['item_name' => $item->item_name]),
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

        $offers = Offer::with(['items', 'seller' => function ($query) {
            $query->withAvg('receivedRatings', 'rating')
                ->withCount('receivedRatings');
        }])
            ->withCoordinates()
            ->whereNull('closed_at')
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
            'closing_time' => Carbon::parse($validated['closing_time'])->format('Y-m-d H:i:s'),
            'arrival_time' => Carbon::parse($validated['arrival_time'])->format('Y-m-d H:i:s'),
            'has_cod_payment' => $validated['has_cod_payment'] ?? false,
            'is_completed' => false,
        ]);

        if (! empty($validated['items'] ?? [])) {
            $offer->items()->createMany($validated['items']);
        }

        $offer->paymentMethods()->sync($validated['payment_method_ids'] ?? []);

        $this->chatService->getOrCreateGroupConversation($offer);

        foreach ($request->user()->followers as $follower) {
            $follower->notify(new FollowingUserNewOfferNotification($request->user(), $offer));
        }

        if (! empty($validated['based_on_request_id'])) {
            $reqModel = \App\Models\Request::find($validated['based_on_request_id']);
            if ($reqModel) {
                $voters = $reqModel->voters()->where('users.user_id', '!=', $request->user()->user_id)->get();
                foreach ($voters as $voter) {
                    $voter->notify(new OfferCreatedFromLikedRequestNotification($offer, $reqModel));
                }
            }
        }

        return response()->json([
            'message' => __('Offer created successfully.'),
            'offer' => $offer->load('items'),
        ], 201);
    }

    public function show(Offer $offer)
    {
        $offer = Offer::withCoordinates()->with(['items', 'seller' => function ($query) {
            $query->withAvg('receivedRatings', 'rating')
                ->withCount('receivedRatings');
        }])->findOrFail($offer->offer_id);

        $userId = auth()->id();
        $hasRatedSeller = false;

        if ($userId) {
            $hasRatedSeller = Rating::where('rater_id', $userId)
                ->where('rated_user_id', $offer->seller_id)
                ->where('offer_id', $offer->offer_id)
                ->exists();
        }

        $offer->seller->has_rated_seller = $hasRatedSeller;

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

        $offerIds = $orders->pluck('offer_id');
        $ratedOfferIds = Rating::where('rater_id', $userId)
            ->whereIn('offer_id', $offerIds)
            ->pluck('offer_id')
            ->flip();

        return response()->json([
            'status' => 'success',
            'data' => $orders->map(fn ($offerBuyer) => [
                'offer_id' => $offerBuyer->offer_id,
                'merchant_name' => $offerBuyer->offer->merchant_name,
                'merchant_id' => $offerBuyer->offer->seller_id,
                'category' => $offerBuyer->offer->category,
                'location_label' => $offerBuyer->offer->location_label,
                'closing_time' => $offerBuyer->offer->closing_time,
                'arrival_time' => $offerBuyer->offer->arrival_time,
                'closed_at' => $offerBuyer->offer->closed_at,
                'arrived_at' => $offerBuyer->offer->arrived_at,
                'is_confirmed' => $offerBuyer->is_confirmed,
                'payment_proof_url' => $offerBuyer->payment_proof_url,
                'joined_at' => $offerBuyer->created_at,
                'payment_submitted_at' => $offerBuyer->payment_submitted_at,
                'confirmed_at' => $offerBuyer->confirmed_at,
                'created_at' => $offerBuyer->created_at,
                'is_rated' => $ratedOfferIds->has($offerBuyer->offer_id),
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
                'is_confirmed' => $offerBuyer->is_confirmed,
                'payment_proof_url' => $offerBuyer->payment_proof_url,
                'joined_at' => $offerBuyer->created_at,
                'payment_submitted_at' => $offerBuyer->payment_submitted_at,
                'confirmed_at' => $offerBuyer->confirmed_at,
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
                'message' => __('Only the seller can close this offer.'),
            ], 403);
        }

        if ($offer->closed_at) {
            return response()->json([
                'message' => __('Offer is already closed.'),
            ], 409);
        }

        $offer->closed_at = now();
        $offer->save();

        foreach ($offer->buyers as $buyer) {
            $buyer->notify(new OfferClosedNotification($offer));
        }

        $conversation = $this->chatService->getOrCreateGroupConversation($offer);
        $this->chatService->postSystemMessage(
            $conversation,
            'SYS_OFFER_CLOSED',
            ['merchant_name' => $offer->merchant_name],
            'lock',
            'info',
        );

        broadcast(new OfferUpdated($offer->offer_id));

        return response()->json([
            'message' => __('Offer closed successfully.'),
            'offer' => $offer->fresh(['items']),
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
                'message' => __('Only the seller can mark this offer as arrived.'),
            ], 403);
        }

        if ($offer->arrived_at) {
            return response()->json([
                'message' => __('Offer has already been marked as arrived.'),
            ], 409);
        }

        $offer->arrived_at = now();
        $offer->save();

        broadcast(new OfferUpdated($offer->offer_id));

        foreach ($offer->buyers as $buyer) {
            $buyer->notify(new ItemsArrivedNotification($offer));
        }

        $conversation = $this->chatService->getOrCreateGroupConversation($offer->fresh());
        $chatClosesAt = $conversation->chatClosesAt();
        $this->chatService->postSystemMessage(
            $conversation,
            'SYS_ITEMS_ARRIVED',
            [
                'merchant_name' => $offer->merchant_name,
                'chat_closes_at' => $chatClosesAt->toDayDateTimeString(),
            ],
            'local_shipping',
            'success',
            ['chat_closes_at' => $chatClosesAt->toISOString()],
        );

        return response()->json([
            'message' => __('Offer marked as arrived successfully.'),
            'offer' => $offer->fresh(['items']),
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
                'message' => __('You do not have an order in this offer.'),
            ], 404);
        }

        $offerBuyer->payment_submitted_at = now();
        if (! empty($validated['payment_proof_url'])) {
            $offerBuyer->payment_proof_url = $validated['payment_proof_url'];
        }
        $offerBuyer->save();

        broadcast(new OfferUpdated($offer->offer_id));

        $offer->seller->notify(new PaymentProofUploadedNotification($request->user(), $offer));

        return response()->json([
            'message' => __('Payment proof sent successfully.'),
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
                'message' => __('You cannot join your own offer.'),
            ], 403);
        }

        if ($error = $this->validateOfferOpen($offer)) {
            return $error;
        }

        $existing = OfferBuyer::where('offer_id', $offer->offer_id)
            ->where('buyer_id', $userId)
            ->first();

        if (! $existing) {
            OfferBuyer::create([
                'offer_id' => $offer->offer_id,
                'buyer_id' => $userId,
            ]);

            $offer->seller->notify(new BuyerJoinedNotification($request->user(), $offer));

            $conversation = $this->chatService->getOrCreateGroupConversation($offer);
            $this->chatService->postSystemMessage(
                $conversation,
                'SYS_BUYER_JOINED',
                [
                    'user_name' => $request->user()->name,
                    'merchant_name' => $offer->merchant_name,
                ],
                'group_add',
                'info',
            );
        }

        return response()->json([
            'message' => __('Successfully joined the offer.'),
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
                'message' => __('Only the seller can complete this offer.'),
            ], 403);
        }

        if ($offer->is_completed) {
            return response()->json([
                'message' => __('Offer is already complete.'),
            ], 409);
        }

        $offer->is_completed = true;
        $offer->save();

        foreach ($offer->buyers as $buyer) {
            $buyer->notify(new OfferCompletedNotification($offer));
        }

        $conversation = $this->chatService->getOrCreateGroupConversation($offer);
        $this->chatService->postSystemMessage(
            $conversation,
            'SYS_OFFER_COMPLETED',
            ['merchant_name' => $offer->merchant_name],
            'check_circle',
            'success',
        );

        broadcast(new OfferUpdated($offer->offer_id));

        return response()->json([
            'message' => __('Offer completed successfully.'),
            'offer' => $offer->fresh(),
        ], 200);
    }

    public function placeOrder(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->closed_at) {
            return response()->json([
                'message' => __('Offer is already closed. You cannot make a new order.'),
            ], 403);
        }
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

            if ($error = $this->validateOfferOpen($offer)) {
                return $error;
            }

            $existing = OfferBuyer::where('offer_id', $offer->offer_id)
                ->where('buyer_id', $userId)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => __('You already have an order in this offer.'),
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
            ]);

            foreach ($validated['items'] as $orderItem) {
                $offerBuyer->items()->create([
                    'item_id' => $orderItem['item_id'],
                    'quantity' => $orderItem['quantity'],
                    'notes' => $orderItem['notes'] ?? null,
                ]);
            }

            $conversation = $this->chatService->getOrCreateGroupConversation($offer);
            $this->chatService->postSystemMessage(
                $conversation,
                'SYS_BUYER_JOINED',
                [
                    'user_name' => $request->user()->name,
                    'merchant_name' => $offer->merchant_name,
                ],
                'group_add',
                'info',
            );

            $offer->seller->notify(new OrderPlacedNotification($request->user(), $offer));

            DB::afterCommit(fn () => broadcast(new OfferUpdated($offer->offer_id)));

            return response()->json([
                'message' => __('Order processed successfully and you have joined.'),
                'offer' => $offer->load('items', 'buyers'),
            ], 200);
        });
    }

    public function updateOrder(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->closed_at) {
            return response()->json([
                'message' => __('Offer is already closed. You cannot change your order.'),
            ], 403);
        }
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

            DB::afterCommit(fn () => broadcast(new OfferUpdated($offer->offer_id)));

            return response()->json([
                'message' => __('Order updated successfully.'),
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

            if ($error = $this->validateOfferOpen($offer)) {
                return $error;
            }

            $offerBuyer = OfferBuyer::where('offer_id', $offer->offer_id)
                ->where('buyer_id', $userId)
                ->with('items')
                ->first();

            if (! $offerBuyer) {
                return response()->json([
                    'message' => __('You do not have an order in this offer.'),
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

            DB::afterCommit(fn () => broadcast(new OfferUpdated($offer->offer_id)));

            return response()->json([
                'message' => __('Order processed successfully.'),
                'offer' => $offer->fresh()->load('items', 'buyers'),
            ], 200);
        });
    }

    public function cancelOrder(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->closed_at) {
            return response()->json([
                'message' => __('Offer is already closed. You cannot cancel your order.'),
            ], 403);
        }
        $userId = $request->user()->user_id;

        return DB::transaction(function () use ($offer, $userId, $request) {
            $offerBuyer = OfferBuyer::where('offer_id', $offer->offer_id)
                ->where('buyer_id', $userId)
                ->with('items')
                ->first();

            if (! $offerBuyer) {
                return response()->json([
                    'message' => __('You do not have an order in this offer.'),
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

            $conversation = $this->chatService->getOrCreateGroupConversation($offer);
            $this->chatService->postSystemMessage(
                $conversation,
                'SYS_BUYER_LEFT',
                [
                    'user_name' => $request->user()->name,
                    'merchant_name' => $offer->merchant_name,
                ],
                'group_remove',
                'info',
            );

            DB::afterCommit(fn () => broadcast(new OfferUpdated($offer->offer_id)));

            return response()->json([
                'message' => __('Order cancelled and stock returned successfully.'),
                'offer' => $offer->fresh()->load('items', 'buyers'),
            ], 200);
        });
    }

    /**
     * List all offers created by the authenticated seller.
     */
    public function myOffers(Request $request): JsonResponse
    {
        $offers = Offer::where('seller_id', $request->user()->user_id)
            ->with(['items'])
            ->withCoordinates()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $offers,
        ], 200);
    }

    /**
     * List all orders (offer_buyers) placed on this offer, seller-only.
     */
    public function orders(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->seller_id !== $request->user()->user_id) {
            return response()->json([
                'message' => __('Only the seller can view orders for this offer.'),
            ], 403);
        }

        $offerBuyers = OfferBuyer::where('offer_id', $offer->offer_id)
            ->with(['buyer', 'items.item'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $offerBuyers->map(fn (OfferBuyer $offerBuyer) => [
                'offer_buyer_id' => $offerBuyer->offer_buyer_id,
                'buyer' => [
                    'user_id' => $offerBuyer->buyer->user_id,
                    'name' => $offerBuyer->buyer->name,
                    'avatar' => $offerBuyer->buyer->avatar,
                ],
                'is_confirmed' => $offerBuyer->is_confirmed,
                'payment_proof_url' => $offerBuyer->payment_proof_url,
                'joined_at' => $offerBuyer->created_at,
                'payment_submitted_at' => $offerBuyer->payment_submitted_at,
                'confirmed_at' => $offerBuyer->confirmed_at,
                'items' => $offerBuyer->items->map(fn ($buyerItem) => [
                    'item' => $buyerItem->item,
                    'quantity' => $buyerItem->quantity,
                    'notes' => $buyerItem->notes,
                ]),
            ]),
        ], 200);
    }

    /**
     * Seller confirms a buyer's payment for this offer.
     */
    public function confirmPayment(Request $request, Offer $offer, OfferBuyer $offerBuyer): JsonResponse
    {
        if ($offer->seller_id !== $request->user()->user_id) {
            return response()->json([
                'message' => __('Only the seller can confirm this payment.'),
            ], 403);
        }

        if ($offerBuyer->offer_id !== $offer->offer_id) {
            return response()->json([
                'message' => __('Order not found in this offer.'),
            ], 404);
        }

        $offerBuyer->is_confirmed = true;
        $offerBuyer->confirmed_at = now();
        $offerBuyer->save();

        $offerBuyer->buyer->notify(new PaymentConfirmedNotification($offer));

        // Once every buyer on this (closed) offer is confirmed, stamp the
        // moment for the Manage Offer timeline — only ever set once.
        if ($offer->payments_confirmed_at === null
            && $offer->closed_at !== null
            && ! OfferBuyer::where('offer_id', $offer->offer_id)->where('is_confirmed', false)->exists()
        ) {
            $offer->payments_confirmed_at = now();
            $offer->save();
        }

        broadcast(new OfferUpdated($offer->offer_id));

        return response()->json([
            'message' => __('Payment confirmed successfully.'),
            'offer_buyer' => $offerBuyer->fresh(),
            'offer' => $offer->fresh(['items']),
        ], 200);
    }

    /**
     * List the payment methods enabled for this offer.
     */
    public function getPaymentMethods(Request $request, Offer $offer): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $offer->paymentMethods,
        ], 200);
    }

    /**
     * Seller edits an existing offer: its own fields, its full item set, and
     * its enabled payment methods. Items are reduced first with a LIFO
     * (most-recently-joined-buyer-first) rollback against existing orders,
     * before the new field values are applied.
     */
    public function update(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->closed_at) {
            return response()->json([
                'message' => __('Offer is already closed. You cannot change offer details.'),
            ], 403);
        }
        if ($offer->seller_id !== $request->user()->user_id) {
            return response()->json([
                'message' => __('Only the seller can edit this offer.'),
            ], 403);
        }

        $validated = Validator::make($request->all(), [
            'category' => 'required|string|in:food,electronics,fashion,home,beauty,gaming,sports,other',
            'merchant_name' => ['nullable', 'string', 'max:64'],
            'location_label' => ['nullable', 'string', 'max:255'],
            'location_lat' => ['required', 'numeric', 'between:-90,90'],
            'location_lng' => ['required', 'numeric', 'between:-180,180'],
            'closing_time' => ['required', 'date'],
            // Full-datetime ordering only matters when both sides fall on
            // the same day — see the after() closure below, which also
            // covers the coarser "date must always be closing <= arrival"
            // rule regardless of time-of-day.
            'arrival_time' => ['required', 'date'],
            'has_cod_payment' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['nullable', 'integer', 'exists:items,item_id'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.item_price' => ['required', 'numeric', 'min:0'],
            'items.*.item_url' => ['nullable', 'string', 'url'],
            'items.*.slot' => ['required', 'integer', 'min:0'],
            'items.*.image_url' => ['nullable', 'string', 'url'],
            'payment_method_ids' => ['sometimes', 'array'],
            'payment_method_ids.*' => ['integer', 'exists:payment_methods,payment_method_id'],
        ])->after(function ($validator) use ($request) {
            $closing = $request->input('closing_time');
            $arrival = $request->input('arrival_time');

            if (! $closing || ! $arrival) {
                return;
            }

            $closingTime = Carbon::parse($closing);
            $arrivalTime = Carbon::parse($arrival);

            if ($arrivalTime->toDateString() < $closingTime->toDateString()) {
                $validator->errors()->add('arrival_time', 'Items must arrive on or after the offer closing date.');
            } elseif ($arrivalTime->toDateString() === $closingTime->toDateString() && $arrivalTime->lt($closingTime)) {
                $validator->errors()->add('arrival_time', 'On the same day, items must arrive at or after the offer closing time.');
            }
        })->validate();

        return DB::transaction(function () use ($validated, $offer) {
            // Step 1: snapshot pre-edit state.
            $oldItems = $offer->items()->get()->keyBy('item_id');
            $oldOfferBuyers = OfferBuyer::where('offer_id', $offer->offer_id)
                ->with('items.item')
                ->get()
                ->keyBy('offer_buyer_id');
            $oldArrivalTime = $offer->arrival_time?->toDateTimeString();
            $oldCategory = $offer->category;
            $oldMerchantName = $offer->merchant_name;

            $payloadItems = collect($validated['items']);
            $payloadItemIds = $payloadItems->pluck('item_id')->filter()->values();

            $removedBuyers = collect(); // offer_buyer_id => Offer\Buyer (fully removed)
            $adjustedBuyers = collect(); // offer_buyer_id => ['buyer' => User, 'items' => [itemName => newQty]]

            // Step 2 & 3: for every existing item no longer present (or shrunk),
            // compute the deficit and roll back buyer_items LIFO.
            foreach ($oldItems as $itemId => $item) {
                $newSlot = $payloadItemIds->contains($itemId)
                    ? (int) $payloadItems->firstWhere('item_id', $itemId)['slot']
                    : 0;

                $deficit = $item->current_slot - $newSlot;
                if ($deficit <= 0) {
                    continue;
                }

                $buyerItems = DB::table('buyer_items')
                    ->join('offer_buyers', 'buyer_items.offer_buyer_id', '=', 'offer_buyers.offer_buyer_id')
                    ->where('buyer_items.item_id', $itemId)
                    ->where('offer_buyers.offer_id', $offer->offer_id)
                    ->orderBy('offer_buyers.created_at', 'desc')
                    ->select('buyer_items.id', 'buyer_items.offer_buyer_id', 'buyer_items.quantity')
                    ->get();

                foreach ($buyerItems as $buyerItem) {
                    if ($deficit <= 0) {
                        break;
                    }

                    $consumed = min($deficit, $buyerItem->quantity);
                    $deficit -= $consumed;

                    $offerBuyerModel = $oldOfferBuyers->get($buyerItem->offer_buyer_id);

                    if ($consumed >= $buyerItem->quantity) {
                        DB::table('buyer_items')->where('id', $buyerItem->id)->delete();
                    } else {
                        $remaining = $buyerItem->quantity - $consumed;
                        DB::table('buyer_items')->where('id', $buyerItem->id)->update(['quantity' => $remaining]);
                    }

                    if ($offerBuyerModel) {
                        $bucket = $adjustedBuyers->get($buyerItem->offer_buyer_id, [
                            'buyer' => $offerBuyerModel->buyer,
                            'items' => [],
                        ]);
                        $bucket['items'][$item->item_name] = max(0, $buyerItem->quantity - $consumed);
                        $adjustedBuyers->put($buyerItem->offer_buyer_id, $bucket);
                    }
                }
            }

            // Buyers left with zero buyer_items rows are fully removed.
            foreach ($adjustedBuyers as $offerBuyerId => $bucket) {
                $remainingCount = DB::table('buyer_items')->where('offer_buyer_id', $offerBuyerId)->count();
                if ($remainingCount === 0) {
                    $offerBuyerModel = $oldOfferBuyers->get($offerBuyerId);
                    $removedItemNames = collect($oldOfferBuyers->get($offerBuyerId)?->items ?? [])
                        ->pluck('item.item_name')
                        ->filter()
                        ->values()
                        ->all();
                    $removedBuyers->put($offerBuyerId, [
                        'buyer' => $offerBuyerModel?->buyer,
                        'removedItemNames' => $removedItemNames,
                    ]);
                    $adjustedBuyers->forget($offerBuyerId);
                }
            }

            foreach ($removedBuyers->keys() as $offerBuyerId) {
                OfferBuyer::where('offer_buyer_id', $offerBuyerId)->delete();
            }

            // Step 4: apply new item field values / create new items.
            foreach ($payloadItems as $payloadItem) {
                if (! empty($payloadItem['item_id']) && $oldItems->has($payloadItem['item_id'])) {
                    $item = $oldItems->get($payloadItem['item_id']);
                    $item->item_name = $payloadItem['item_name'];
                    $item->item_price = $payloadItem['item_price'];
                    $item->item_url = $payloadItem['item_url'] ?? null;
                    $item->slot = $payloadItem['slot'];
                    $item->image_url = $payloadItem['image_url'] ?? null;
                    // Recompute current_slot from the remaining buyer_items,
                    // to stay consistent after any LIFO trimming above.
                    $item->current_slot = (int) DB::table('buyer_items')->where('item_id', $item->item_id)->sum('quantity');
                    $item->save();
                } else {
                    $offer->items()->create([
                        'item_name' => $payloadItem['item_name'],
                        'item_price' => $payloadItem['item_price'],
                        'item_url' => $payloadItem['item_url'] ?? null,
                        'slot' => $payloadItem['slot'],
                        'image_url' => $payloadItem['image_url'] ?? null,
                        'current_slot' => 0,
                    ]);
                }
            }

            // Items removed entirely from the payload get deleted (their
            // buyer_items were already fully cleared out above).
            $removedItemIds = $oldItems->keys()->diff($payloadItemIds);
            if ($removedItemIds->isNotEmpty()) {
                Item::whereIn('item_id', $removedItemIds)->delete();
            }

            // Step 5: update the offer's own fields.
            $offer->category = $validated['category'];
            $offer->merchant_name = $validated['merchant_name'] ?? '';
            $offer->location_label = $validated['location_label'] ?? null;
            $offer->location = Offer::makePoint($validated['location_lat'], $validated['location_lng']);
            $offer->closing_time = Carbon::parse($validated['closing_time'])->format('Y-m-d H:i:s');
            $offer->arrival_time = Carbon::parse($validated['arrival_time'])->format('Y-m-d H:i:s');
            $offer->has_cod_payment = $validated['has_cod_payment'] ?? false;

            // Reset notifications state on edit
            $offer->notified_sold_out_early = false;
            $offer->notified_closing_reached = false;

            $offer->save();

            // Step 6: sync payment methods.
            $offer->paymentMethods()->sync($validated['payment_method_ids'] ?? []);

            // Step 7: notify removed / adjusted buyers, and detect disruptive edits for everyone else.
            foreach ($removedBuyers as $info) {
                if ($info['buyer']) {
                    $info['buyer']->notify(new BuyerRemovedFromOfferNotification($offer, $info['removedItemNames']));
                }
            }

            foreach ($adjustedBuyers as $bucket) {
                foreach ($bucket['items'] as $itemName => $newQuantity) {
                    $bucket['buyer']?->notify(new ItemAdjustedNotification($offer, $itemName, $newQuantity));
                }
            }

            $arrivalChanged = $oldArrivalTime !== $offer->arrival_time?->toDateTimeString();
            $categoryChanged = $oldCategory !== $offer->category;
            $merchantChanged = $oldMerchantName !== $offer->merchant_name;
            $newPricesByItemId = $payloadItems->filter(fn ($i) => ! empty($i['item_id']))->keyBy('item_id');

            foreach ($oldOfferBuyers as $offerBuyerId => $offerBuyerModel) {
                if ($removedBuyers->has($offerBuyerId) || $adjustedBuyers->has($offerBuyerId)) {
                    continue;
                }

                $changes = [];
                if ($arrivalChanged) {
                    $changes['arrival_time'] = ['from' => $oldArrivalTime, 'to' => $offer->arrival_time?->toDateTimeString()];
                }
                if ($categoryChanged) {
                    $changes['category'] = ['from' => $oldCategory, 'to' => $offer->category];
                }
                if ($merchantChanged) {
                    $changes['merchant_name'] = ['from' => $oldMerchantName, 'to' => $offer->merchant_name];
                }

                $priceIncreases = [];
                foreach ($offerBuyerModel->items as $buyerItem) {
                    $newItem = $newPricesByItemId->get($buyerItem->item_id);
                    if ($newItem && $buyerItem->item && (float) $newItem['item_price'] > (float) $buyerItem->item->item_price) {
                        $priceIncreases[] = [
                            'item_name' => $buyerItem->item->item_name,
                            'from' => (float) $buyerItem->item->item_price,
                            'to' => (float) $newItem['item_price'],
                        ];
                    }
                }
                if (! empty($priceIncreases)) {
                    $changes['item_price_increase'] = $priceIncreases;
                }

                $disruptive = ! empty($changes);
                $offerBuyerModel->buyer?->notify(new OfferEditedNotification($offer, $disruptive, $changes));
            }

            $conversation = $this->chatService->getOrCreateGroupConversation($offer);
            $this->chatService->postSystemMessage(
                $conversation,
                'SYS_OFFER_UPDATED',
                ['merchant_name' => $offer->merchant_name],
                'edit',
                'info',
            );

            DB::afterCommit(fn () => broadcast(new OfferUpdated($offer->offer_id)));

            return response()->json([
                'message' => __('Offer updated successfully.'),
                'offer' => $offer->fresh()->load('items'),
            ], 200);
        });
    }

    /**
     * Seller deletes an offer, notifying every current buyer first.
     */
    public function destroy(Request $request, Offer $offer): JsonResponse
    {
        if ($offer->closed_at) {
            return response()->json([
                'message' => __('Offer is already closed. You cannot delete this offer.'),
            ], 403);
        }
        if ($offer->seller_id !== $request->user()->user_id) {
            return response()->json([
                'message' => __('Only the seller can delete this offer.'),
            ], 403);
        }

        foreach ($offer->buyers as $buyer) {
            $buyer->notify(new OfferDeletedNotification($offer));
        }

        $offerId = $offer->offer_id;
        $offer->delete();

        broadcast(new OfferUpdated($offerId));

        return response()->json([
            'message' => __('Offer deleted successfully.'),
        ], 200);
    }

    /**
     * Buyer acknowledges a disruptive offer edit: either keep their order as-is,
     * or leave the offer (same rollback as cancelOrder()).
     */
    public function respondToChanges(Request $request, Offer $offer): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['keep', 'leave'])],
        ]);

        if ($validated['action'] === 'leave') {
            return $this->cancelOrder($request, $offer);
        }

        $userId = $request->user()->user_id;
        $offerBuyer = OfferBuyer::where('offer_id', $offer->offer_id)
            ->where('buyer_id', $userId)
            ->first();

        if (! $offerBuyer) {
            return response()->json([
                'message' => __('You do not have an order in this offer.'),
            ], 404);
        }

        return response()->json([
            'message' => __('Your order has been kept.'),
        ], 200);
    }

    /**
     * Uploads an image (item image or payment proof) to public storage and
     * returns its public URL. Assumes `php artisan storage:link` has already
     * been run so the `public` disk is web-accessible.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
        ]);

        $path = $validated['file']->store('uploads/items', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ], 200);
    }

    /**
     * Deletes a previously-uploaded file that never ended up attached to
     * anything — e.g. a payment-proof image uploaded via uploadImage() but
     * abandoned when the buyer reloaded before clicking "Complete payment".
     * Only ever touches paths under uploads/items on the public disk.
     */
    public function deleteUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string'],
        ]);

        $marker = '/storage/';
        $pos = strpos($validated['url'], $marker);

        if ($pos !== false) {
            $path = substr($validated['url'], $pos + strlen($marker));

            if (str_starts_with($path, 'uploads/items/') && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return response()->json(['message' => 'ok'], 200);
    }
}
