<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperPage
 */
class Page extends Model
{
    use HasFactory, Sortable;

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

    protected array $sortable = [
        'order',
        'created_at',
        'updated_at',
    ];
}
