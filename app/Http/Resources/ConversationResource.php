<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $owner = $this->participants->firstWhere('role', 'owner');

        return [
            'id' => $this->id,
            'type' => $this->type,
            'offer_id' => $this->offer_id,
            'offer_merchant_name' => $this->offer?->merchant_name,
            'owner' => $owner ? [
                'user_id' => $owner->user->user_id,
                'name' => $owner->user->name,
                'avatar' => $owner->user->avatar,
            ] : null,
            'participants' => $this->participants->map(fn ($participant) => [
                'user_id' => $participant->user->user_id,
                'name' => $participant->user->name,
                'avatar' => $participant->user->avatar,
                'role' => $participant->role,
                'left_at' => $participant->left_at?->toISOString(),
            ]),
            'chat_open' => $this->isOpen(),
            'chat_closes_at' => $this->chatClosesAt()?->toISOString(),
        ];
    }
}
