<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $primaryKey = 'item_id';

    protected $fillable =[
        'offer_id',
        'item_name',
        'item_price',
        'item_url',
        'current_slot',
        'slot',
        'image_url'
    ];

    protected $casts = [
        'item_price' => 'decimal:2',
        'current_slot' => 'integer',
        'slot' => 'integer'
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }
}
