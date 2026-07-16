<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'username', 'google_id', 'avatar'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $primaryKey = 'user_id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'user_id', 'user_id');
    }

    public function joinedOffers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'offer_buyers', 'buyer_id', 'offer_id')->withTimestamps();
    }

    public function offerOrders()
    {
        return $this->hasMany(OfferBuyer::class, 'buyer_id', 'user_id');
    }

    // Followers: users yang follow user ini
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    // Following: users yang di-follow oleh user ini
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    // Ratings yang diterima user ini
    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'rated_user_id', 'user_id');
    }

    // Ratings yang diberikan user ini
    public function givenRatings()
    {
        return $this->hasMany(Rating::class, 'rater_id', 'user_id');
    }

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'user_id', 'user_id');
    }
}
