<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

/**
 * @mixin IdeHelperPackageTemplate
 */
class PackageTemplate extends Model
{
    use HasFactory, Searchable, HasMetadata;

    protected $fillable = [
        'name',
        'weight',
        'width',
        'height',
        'depth',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    protected array $criteria = [
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
    ];
}
