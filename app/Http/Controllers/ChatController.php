<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    /**
     * Find or create the persistent 1:1 conversation between the
     * authenticated user and the given user.
     */
    public function privateConversation(Request $request, string $userId): JsonResponse
    {
        $otherUser = User::findOrFail($userId);
        $me = $request->user();

        if ($otherUser->user_id === $me->user_id) {
            return response()->json(['message' => 'You cannot chat with yourself.'], 422);
        }

        $conversation = $this->chatService->findOrCreatePrivateConversation($me, $otherUser)
            ->load('participants.user');

        return response()->json(new ConversationResource($conversation));
    }

    /**
     * Get (or lazily create) the group-chat conversation for this offer.
     * Only the seller or a current/former buyer may access it.
     */
    public function offerConversation(Request $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->user_id;

        if (! $this->canAccessOfferConversation($offer, $userId)) {
            return response()->json(['message' => 'You do not have access to this offer\'s chat.'], 403);
        }

        $conversation = $this->chatService->getOrCreateGroupConversation($offer)
            ->load('participants.user', 'offer');

        return response()->json(new ConversationResource($conversation));
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        if ($error = $this->authorizeParticipant($request, $conversation)) {
            return $error;
        }

        $conversation->load('participants.user', 'offer');

        return response()->json(new ConversationResource($conversation));
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        if ($error = $this->authorizeParticipant($request, $conversation)) {
            return $error;
        }

        $messages = $conversation->messages()
            ->visibleTo($request->user()->user_id)
            ->with('sender', 'target')
            ->orderBy('created_at')
            ->get();

        return response()->json(MessageResource::collection($messages));
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        if ($error = $this->authorizeParticipant($request, $conversation)) {
            return $error;
        }

        if (! $conversation->isOpen()) {
            return response()->json(['message' => 'This chat is closed and no longer accepts new messages.'], 403);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:4000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
            'target_user_id' => ['nullable', 'string', 'exists:users,user_id'],
        ]);

        if (empty($validated['body']) && ! $request->hasFile('image')) {
            return response()->json(['message' => 'A message must have text or an image.'], 422);
        }

        $sender = $request->user();
        $target = null;

        if (! empty($validated['target_user_id'])) {
            $target = User::findOrFail($validated['target_user_id']);

            $isParticipant = $conversation->participants()->where('user_id', $target->user_id)->exists();
            if (! $isParticipant) {
                return response()->json(['message' => 'The selected recipient is not part of this chat.'], 422);
            }
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads/chat', 'public');
            $imageUrl = Storage::disk('public')->url($path);
        }

        $message = $this->chatService->postMessage(
            $conversation,
            $sender,
            $validated['body'] ?? null,
            $imageUrl,
            $target,
        );

        return response()->json(new MessageResource($message->load('sender', 'target')), 201);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): ?JsonResponse
    {
        $userId = $request->user()->user_id;

        $isParticipant = $conversation->participants()->where('user_id', $userId)->exists();

        if (! $isParticipant) {
            return response()->json(['message' => 'You do not have access to this chat.'], 403);
        }

        return null;
    }

    private function canAccessOfferConversation(Offer $offer, string $userId): bool
    {
        if ($offer->seller_id === $userId) {
            return true;
        }

        return $offer->buyers()->where('user_id', $userId)->exists()
            || $offer->conversation()->whereHas('participants', fn ($q) => $q->where('user_id', $userId))->exists();
    }
}
