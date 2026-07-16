<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

#[Fillable(['requester_id', 'location_label', 'location', 'item_name', 'category', 'arrival_time', 'total_votes'])]
class Request extends Model
{
    protected $table = 'requests';
    protected $primaryKey = 'request_id';

    protected $hidden = ['location'];

    protected function casts(): array
    {
        return [
            'arrival_time' => 'datetime:Y-m-d H:i:s',
            'total_votes' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Build the raw SQL expression for a `location` POINT(lng, lat) SRID 4326 value.
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
     * Restrict to requests within $radiusMeters (great-circle, via ST_Distance_Sphere)
     * of ($lat, $lng).
     */
    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusMeters): Builder
    {
        $column = $query->qualifyColumn('location');

        return $query->whereRaw(
            "ST_Distance_Sphere(POINT(ST_Y({$column}), ST_X({$column})), POINT(?, ?)) <= ?",
            [$lng, $lat, $radiusMeters],
        );
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id', 'user_id');
    }

    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'request_voters', 'request_id', 'user_id');
    }
}