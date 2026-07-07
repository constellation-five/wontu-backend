<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['offer_buyer_id', 'item_id', 'quantity', 'notes'])]
class BuyerItem extends Model
{
    public function offerBuyer(): BelongsTo
    {
        return $this->belongsTo(OfferBuyer::class, 'offer_buyer_id', 'offer_buyer_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }
}
