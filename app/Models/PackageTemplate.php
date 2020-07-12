<?php

namespace App\Models;

use App\Models\Swagger\PackageTemplateSwagger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageTemplate extends Model implements PackageTemplateSwagger
{
    protected $fillable = [
        'name',
        'weight',
        'width',
        'height',
        'depth',
    ];
}
