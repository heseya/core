<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperStatus
 */
class Status extends Model implements AuditableContract
{
    use HasFactory, Auditable, HasTranslations;

    protected $fillable = [
        'name',
        'color',
        'cancel',
        'description',
        'order',
        'hidden',
        'no_notifications',
        'published',
    ];

    protected $casts = [
        'cancel' => 'boolean',
        'hidden' => 'boolean',
        'no_notifications' => 'boolean',
        'published' => 'bool',
    ];

    protected $translatable = [
        'name',
        'description',
        'published',
    ];

    protected $attributes = [
        'hidden' => false,
        'no_notifications' => false,
    ];

    public function getPublishedAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
