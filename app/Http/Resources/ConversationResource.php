<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $participants = [];
        $ownerData = null;

        if ($this->type === 'offer_group' && $this->offer) {
            $seller = $this->offer->seller;
            if ($seller) {
                $ownerData = [
                    'user_id' => $seller->user_id,
                    'name' => $seller->name,
                    'avatar' => $seller->avatar,
                ];
                $participants[] = array_merge($ownerData, ['role' => 'owner', 'left_at' => null]);
            }
            foreach ($this->offer->buyers as $buyer) {
                $participants[] = [
                    'user_id' => $buyer->user_id,
                    'name' => $buyer->name,
                    'avatar' => $buyer->avatar,
                    'role' => 'member',
                    'left_at' => null,
                ];
            }
        } else {
            $owner = $this->participants->firstWhere('role', 'owner');
            $ownerData = $owner ? [
                'user_id' => $owner->user->user_id,
                'name' => $owner->user->name,
                'avatar' => $owner->user->avatar,
            ] : null;

            $participants = $this->participants->map(fn ($participant) => [
                'user_id' => $participant->user->user_id,
                'name' => $participant->user->name,
                'avatar' => $participant->user->avatar,
                'role' => $participant->role,
                'left_at' => $participant->left_at?->toISOString(),
            ])->toArray();
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'offer_id' => $this->offer_id,
            'offer_merchant_name' => $this->offer?->merchant_name,
            'owner' => $ownerData,
            'participants' => $participants,
            'chat_open' => $this->isOpen(),
            'chat_closes_at' => $this->chatClosesAt()?->toISOString(),
        ];
    }
}
