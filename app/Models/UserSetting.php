<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'notifications', 'language', 'dark_mode'])]
class UserSetting extends Model
{
    protected $primaryKey = 'setting_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'notifications' => 'array',
            'dark_mode' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get default notification settings structure
     */
    public static function getDefaultNotifications(): array
    {
        return [
            'new-offers' => ['push' => false, 'email' => true],
            'offer-updates' => ['push' => false, 'email' => false],
            'expiring-offers' => ['push' => false, 'email' => false],
            'new-messages' => ['push' => false, 'email' => true],
            'account-activity' => ['push' => false, 'email' => false],
        ];
    }
}
