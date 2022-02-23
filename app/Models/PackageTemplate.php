<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperPackageTemplate
 */
class PackageTemplate extends Model
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
