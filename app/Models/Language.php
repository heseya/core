<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperLanguage
 */
class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'iso',
        'name',
        'default',
        'hidden',
    ];

    protected $casts = [
        'default' => 'boolean',
        'hidden' => 'boolean',
    ];

    public function scopeDefault($query)
    {
        return $query->where('default', true)->first();
    }
}
