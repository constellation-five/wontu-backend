<?php

namespace App\Models;

use App\Support\NotificationCategories;
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
     * Get default notification settings structure — one entry per category
     * in NotificationCategories, each of which gates a group of related
     * notification classes (see NotificationCategories::all()).
     */
    public static function getDefaultNotifications(): array
    {
        return collect(NotificationCategories::all())
            ->map(fn () => ['push' => false, 'email' => true])
            ->all();
    }
}
