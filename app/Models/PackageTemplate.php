<?php

namespace App\Models;

use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperPackageTemplate
 */
class PackageTemplate extends Model
{
    use HasFactory, HasMetadata;

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
}
