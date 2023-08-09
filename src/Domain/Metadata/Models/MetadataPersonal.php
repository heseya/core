<?php

declare(strict_types=1);

namespace Domain\Metadata\Models;

use App\Traits\HasUuid;
use Domain\Metadata\Casts\MetadataValue;
use Domain\Metadata\Enums\MetadataType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Models\HasNormalizedDates;

/**
 * @mixin IdeHelperMetadataPersonal
 */
final class MetadataPersonal extends Model
{
    use HasFactory;
    use HasNormalizedDates;
    use HasUuid;

    protected $fillable = [
        'name',
        'value',
        'value_type',
    ];

    protected $casts = [
        'value' => MetadataValue::class,
        'value_type' => MetadataType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
