<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int|string $offerId) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('offers.'.$this->offerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OfferUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'offer_id' => $this->offerId,
        ];
    }
}
