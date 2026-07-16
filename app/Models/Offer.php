<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

#[Fillable(['seller_id', 'category', 'merchant_name', 'location_label', 'location', 'closing_time', 'arrival_time', 'has_cod_payment', 'is_completed'])]
class Offer extends Model
{
    protected $primaryKey = 'offer_id';

    // Raw binary (WKB); always read/write through makePoint()/scopeWithCoordinates()
    // instead, which expose it as plain latitude/longitude floats.
    protected $hidden = ['location'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closing_time' => 'datetime',
            'arrival_time' => 'datetime',
            'closed_at' => 'datetime',
            'arrived_at' => 'datetime',
            'payments_confirmed_at' => 'datetime',
            'has_cod_payment' => 'boolean',
            'is_completed' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Build the raw SQL expression for a `location` POINT(lng, lat) SRID 4326 value.
     *
     * MySQL 8's default axis order for SRID 4326 text is lat/long, which is the
     * opposite of this app's lng/lat convention (LatLngLiteral, GeoJSON) — pinning
     * 'axis-order=long-lat' keeps WKT input as POINT(lng lat) everywhere, and in turn
     * makes ST_X()/ST_Y() below come back as lat/long consistently (verified empirically).
     */
    public static function makePoint(float $lat, float $lng): Expression
    {
        return DB::raw(sprintf(
            "ST_GeomFromText('POINT(%F %F)', 4326, 'axis-order=long-lat')",
            $lng,
            $lat,
        ));
    }

    /**
     * Select latitude/longitude out of the `location` point as plain floats.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->addSelect([
            $query->qualifyColumn('*'),
            DB::raw('ST_X('.$query->qualifyColumn('location').') as latitude'),
            DB::raw('ST_Y('.$query->qualifyColumn('location').') as longitude'),
        ]);
    }

    /**
     * Restrict to offers within $radiusMeters (great-circle, via ST_Distance_Sphere)
     * of ($lat, $lng).
     *
     * ST_Distance_Sphere always treats its POINT args as (longitude, latitude),
     * ignoring SRID axis order — so `location` (stored X=lat, Y=lng, see makePoint())
     * has to be re-packed as POINT(lng, lat) here rather than passed straight through.
     */
    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusMeters): Builder
    {
        $column = $query->qualifyColumn('location');

        return $query->whereRaw(
            "ST_Distance_Sphere(POINT(ST_Y({$column}), ST_X({$column})), POINT(?, ?)) <= ?",
            [$lng, $lat, $radiusMeters],
        );
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
            ->withPivot(['offer_buyer_id', 'is_confirmed', 'payment_proof_url', 'payment_submitted_at', 'confirmed_at'])
            ->withTimestamps();
    }

    public function offerBuyers(): HasMany
    {
        return $this->hasMany(OfferBuyer::class, 'offer_id', 'offer_id');
    }
}
