<?php

namespace App\Models;

use App\Enums\MetadataType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
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
        'value_type' => MetadataType::class,
        'public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopePublic($query): Builder
    {
        return $query->where('public', true);
    }

    public function scopePrivate($query): Builder
    {
        return $query->where('public', false);
    }
}
