<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => $this->sender ? [
                'user_id' => $this->sender->user_id,
                'name' => $this->sender->name,
                'avatar' => $this->sender->avatar,
            ] : null,
            'target_user_id' => $this->target_user_id,
            'body' => $this->body,
            'image_url' => $this->image_url,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
