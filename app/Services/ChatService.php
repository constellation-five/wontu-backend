<?php

namespace App\Services;

use App\Events\ChatMessageBroadcast;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Offer;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;

class ChatService
{
    public function findOrCreatePrivateConversation(User $a, User $b): Conversation
    {
        $existing = Conversation::query()
            ->where('type', 'private')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $a->user_id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $b->user_id))
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = Conversation::create(['type' => 'private']);
        $this->addParticipant($conversation, $a);
        $this->addParticipant($conversation, $b);

        return $conversation;
    }

    public function getOrCreateGroupConversation(Offer $offer): Conversation
    {
        $conversation = $offer->conversation()->first();

        if ($conversation) {
            return $conversation;
        }

        $conversation = Conversation::create([
            'type' => 'offer_group',
            'offer_id' => $offer->offer_id,
        ]);

        return $conversation;
    }

    public function addParticipant(Conversation $conversation, User $user, string $role = 'member'): void
    {
        $participant = $conversation->participants()->where('user_id', $user->user_id)->first();

        if ($participant) {
            if ($participant->left_at !== null) {
                $participant->left_at = null;
                $participant->joined_at = now();
                $participant->save();
            }

            return;
        }

        $conversation->participants()->create([
            'user_id' => $user->user_id,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    public function removeParticipant(Conversation $conversation, User $user): void
    {
        $conversation->participants()
            ->where('user_id', $user->user_id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);
    }

    public function postMessage(
        Conversation $conversation,
        ?User $sender,
        ?string $body,
        ?string $imageUrl = null,
        ?User $target = null,
    ): Message {
        $message = $conversation->messages()->create([
            'sender_id' => $sender?->user_id,
            'target_user_id' => $target?->user_id,
            'body' => $body,
            'image_url' => $imageUrl,
            'type' => 'text',
        ]);

        $this->broadcast($conversation, $message->load('sender', 'target'));

        return $message;
    }

    public function postSystemMessage(
        Conversation $conversation,
        string $templateKey,
        array $params = [],
        string $icon = 'info',
        string $type = 'info',
        array $extraMetadata = [],
    ): Message {
        $message = $conversation->messages()->create([
            'sender_id' => null,
            'target_user_id' => null,
            'body' => null,
            'type' => 'system',
            'metadata' => array_merge([
                'template_key' => $templateKey,
                'params' => $params,
                'icon' => $icon,
                'notification_type' => $type,
            ], $extraMetadata),
        ]);

        $this->broadcast($conversation, $message);

        return $message;
    }

    private function broadcast(Conversation $conversation, Message $message): void
    {
        if ($message->target_user_id !== null) {
            $recipientIds = array_values(array_filter([
                $message->sender_id,
                $message->target_user_id,
            ]));
        } else {
            if ($conversation->type === 'offer_group' && $conversation->offer) {
                $recipientIds = $conversation->offer->buyers()->pluck('users.user_id')->all();
                $recipientIds[] = $conversation->offer->seller_id;
                $recipientIds = array_values(array_unique($recipientIds));
            } else {
                $recipientIds = $conversation->activeParticipants()->pluck('user_id')->all();
            }
        }

        if (empty($recipientIds)) {
            return;
        }

        ChatMessageBroadcast::dispatch($message, $recipientIds);

        if ($message->type !== 'system') {
            $notifyIds = array_diff($recipientIds, [$message->sender_id]);
            if (! empty($notifyIds)) {
                $usersToNotify = User::whereIn('user_id', $notifyIds)->get();
                foreach ($usersToNotify as $user) {
                    $user->notify(new NewChatMessageNotification($message));
                }
            }
        }
    }
}
