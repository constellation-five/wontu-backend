<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'sender_id', 'target_user_id', 'body', 'image_url', 'type', 'metadata'])]
class Message extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id', 'user_id');
    }

    /**
     * A message is visible to a user if it wasn't scoped ("whispered") at
     * all, or if the user is the sender or the whisper's target.
     */
    public function scopeVisibleTo(Builder $query, string $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->whereNull('target_user_id')
                ->orWhere('sender_id', $userId)
                ->orWhere('target_user_id', $userId);
        });
    }
}
