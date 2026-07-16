<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'offer_id'])]
class Conversation extends Model
{
    use HasUuids;

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id', 'id');
    }

    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id', 'id');
    }

    /**
     * Private conversations never close. Offer-group conversations close to
     * new messages one day after the offer's items actually arrived, but
     * their history always stays readable.
     */
    public function isOpen(): bool
    {
        if ($this->type !== 'offer_group') {
            return true;
        }

        $arrivedAt = $this->offer?->arrived_at;

        return $arrivedAt === null || now()->lte($arrivedAt->copy()->addDay());
    }

    public function chatClosesAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->type !== 'offer_group' || $this->offer?->arrived_at === null) {
            return null;
        }

        return $this->offer->arrived_at->copy()->addDay();
    }
}
