<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'rating_id';

    protected $fillable = [
        'rater_id',
        'rated_user_id',
        'offer_id',
        'rating',
    ];

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id', 'user_id');
    }

    public function ratedUser()
    {
        return $this->belongsTo(User::class, 'rated_user_id', 'user_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }
}
