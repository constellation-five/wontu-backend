<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['offer_id', 'item_name', 'item_price', 'item_url', 'current_slot', 'slot', 'image_url'])]
class Item extends Model
{
    protected $primaryKey = 'item_id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_price' => 'decimal:2',
            'current_slot' => 'integer',
            'slot' => 'integer',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }
}
