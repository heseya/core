<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperPage
 */
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'order',
        'name',
        'slug',
        'public',
        'content_html',
    ];

    protected $casts = [
        'public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
