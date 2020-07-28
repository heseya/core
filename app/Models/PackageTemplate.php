<?php

namespace App\Models;

use App\Models\Swagger\PackageTemplateSwagger;

class PackageTemplate extends Model implements PackageTemplateSwagger
{
    protected $fillable = [
        'name',
        'weight',
        'width',
        'height',
        'depth',
    ];

    protected $casts = [
        'weight' => 'float',
        'width' => 'float',
        'height' => 'float',
        'depth' => 'float',
    ];
}
