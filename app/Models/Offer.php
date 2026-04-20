<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $primaryKey = 'offer_id';

    protected $fillable = [
        'seller_id',
        'category',
        'merchant_name',
        'closing_time',
        'arrival_time',
        'has_cod_payment',
        'is_completed'
    ];

    protected $casts = [
        'closing_time' => 'datetime',
        'arrival_time' => 'datetime',
        'has_cod_payment' => 'boolean',
        'is_completed' => 'boolean'
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'offer_id', 'offer_id');
    }

    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'offer_payment_methods', 'offer_id', 'payment_method_id');
    }
}
