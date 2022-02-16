<?php

namespace App\Models;

use App\Models\Interfaces\Translatable;
use App\Traits\HasSeoMetadata;
use Heseya\Sortable\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperPage
 */
class Page extends Model implements AuditableContract, Translatable
{
    use HasFactory, Sortable, Auditable, SoftDeletes, HasSeoMetadata, HasTranslations;

    protected $fillable = [
        'order',
        'name',
        'slug',
        'public',
        'content_html',
        'published',
    ];

    protected $translatable = [
        'name',
        'content_html',
    ];

    protected $casts = [
        'public' => 'boolean',
        'published' => 'array',
    ];

    protected array $sortable = [
        'order',
        'created_at',
        'updated_at',
    ];
}
