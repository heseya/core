<?php

namespace App\Models;

use App\Casts\MetadataValue;
use App\Enums\MetadataType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property MetadataType $value_type;
 * @mixin IdeHelperMetadata
 */
class Metadata extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'value_type',
        'public',
    ];

    protected $casts = [
        'value' => MetadataValue::class,
        'value_type' => MetadataType::class,
        'public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('public', true);
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('public', false);
    }
}
