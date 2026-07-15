<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['offer_id', 'buyer_id', 'is_confirmed', 'payment_proof_url', 'payment_submitted_at', 'confirmed_at'])]
class OfferBuyer extends Model
{
    protected $primaryKey = 'offer_buyer_id';

    protected function casts(): array
    {
        return [
            'is_confirmed' => 'boolean',
            'payment_submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BuyerItem::class, 'offer_buyer_id', 'offer_buyer_id');
    }
}
