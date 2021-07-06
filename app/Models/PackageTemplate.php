<?php

namespace App\Models;

use App\Models\Swagger\PackageTemplateSwagger;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperPackageTemplate
 */
class PackageTemplate extends Model implements PackageTemplateSwagger
{
    use HasFactory;

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
