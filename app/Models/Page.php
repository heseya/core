<?php

namespace App\Models;

use Heseya\Sortable\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperPage
 */
class Page extends Model implements AuditableContract
{
    use HasFactory, Sortable, Auditable;

    protected $fillable = [
        'order',
        'name',
        'slug',
        'public',
        'content_html',
    ];

    protected $casts = [
        'public' => 'boolean',
    ];

    protected array $sortable = [
        'order',
        'created_at',
        'updated_at',
    ];
}
