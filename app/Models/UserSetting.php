<?php

namespace App\Models;

use App\Support\NotificationCategories;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'notifications', 'language', 'theme'])]
class UserSetting extends Model
{
    use HasUuids;

    protected $primaryKey = 'setting_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'notifications' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get default notification settings structure — one entry per category
     * in NotificationCategories, each of which gates a group of related
     * notification classes (see NotificationCategories::all()).
     */
    public static function getDefaultNotifications(): array
    {
        $defaultPushCategories = [
            'new-orders',
            'offer-lifecycle',
            'offer-updates',
            'order-status',
        ];

        return collect(NotificationCategories::all())
            ->mapWithKeys(function ($value, $key) use ($defaultPushCategories) {
                return [
                    $key => [
                        'push' => in_array($key, $defaultPushCategories),
                        'email' => false,
                    ],
                ];
            })
            ->all();
    }
}
