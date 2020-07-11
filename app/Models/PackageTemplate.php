<?php

namespace App\Models;

use App\Models\Swagger\PackageSwagger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageTemplate extends Model implements PackageSwagger
{
    protected $fillable = [
        'name',
        'weight',
        'width',
        'height',
        'depth',
    ];
}
