<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['seller_id', 'category', 'merchant_name', 'closing_time', 'arrival_time', 'has_cod_payment', 'is_completed'])]
class Offer extends Model
{
    protected $primaryKey = 'offer_id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closing_time' => 'datetime:Y-m-d H:i:s',
            'arrival_time' => 'datetime:Y-m-d H:i:s',
            'closed_at' => 'datetime:Y-m-d H:i:s',
            'arrived_at' => 'datetime:Y-m-d H:i:s',
            'has_cod_payment' => 'boolean',
            'is_completed' => 'boolean',
        ];
    }
    

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

    public function buyers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'offer_buyers', 'offer_id', 'buyer_id')
            ->withPivot(['offer_buyer_id', 'is_verified', 'payment_proof_url', 'payment_submitted_at', 'verified_at', 'status'])
            ->withTimestamps();
    }

    public function offerBuyers(): HasMany
    {
        return $this->hasMany(OfferBuyer::class, 'offer_id', 'offer_id');
    }
}
