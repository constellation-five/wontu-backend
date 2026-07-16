<?php

namespace App\Events;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcasts a chat message only to the specific users allowed to see it —
 * either every active participant of the conversation, or just the
 * sender + whisper target when the message is scoped. Reuses each user's
 * existing private notification channel (App.Models.User.{id}) instead of a
 * dedicated per-conversation channel, so no new channel authorization is
 * required.
 */
class ChatMessageBroadcast implements ShouldBroadcastNow
{
    use Dispatchable;

    /**
     * @param  array<int, string>  $recipientUserIds
     */
    public function __construct(
        public readonly Message $message,
        public readonly array $recipientUserIds,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (string $userId) => new PrivateChannel("App.Models.User.{$userId}"),
            $this->recipientUserIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        return (new MessageResource($this->message))->resolve();
    }
}
