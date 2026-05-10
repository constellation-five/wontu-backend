<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['offer_id', 'user_id', 'item_name', 'item_price', 'quantity', 'notes'])]
class OrderItem extends Model
{
    protected $primaryKey = 'order_item_id';

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    protected function casts(): array
    {
        return [
            'item_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }
}
