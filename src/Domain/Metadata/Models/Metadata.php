<?php

declare(strict_types=1);

namespace Domain\Metadata\Models;

use App\Traits\HasUuid;
use Domain\Metadata\Casts\MetadataValue;
use Domain\Metadata\Enums\MetadataType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Models\HasNormalizedDates;

/**
 * @property MetadataType $value_type;
 *
 * @mixin IdeHelperMetadata
 */
final class Metadata extends Model
{
    use HasFactory;
    use HasNormalizedDates;
    use HasUuid;

    protected $fillable = [
        'name',
        'value',
        'value_type',
        'public',
        'model_id',
        'model_type',
    ];

    protected $casts = [
        'value' => MetadataValue::class,
        'value_type' => MetadataType::class,
        'public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @param Builder<self> $query
     *
     * @return Builder<self>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('public', true);
    }

    /**
     * @param Builder<self> $query
     *
     * @return Builder<self>
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('public', false);
    }
}
