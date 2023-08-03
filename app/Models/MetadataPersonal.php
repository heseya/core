<?php

namespace App\Models;

use App\Enums\MetadataType;
use Domain\Metadata\Casts\MetadataValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperMetadataPersonal
 */
class MetadataPersonal extends Model
{
    use HasFactory;

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
