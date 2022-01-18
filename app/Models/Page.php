<?php

namespace App\Models;

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
class Page extends Model implements AuditableContract
{
    use HasFactory, Sortable, Auditable, SoftDeletes, HasSeoMetadata, HasTranslations;

    protected $fillable = [
        'order',
        'name',
        'slug',
        'public',
        'content_html',
    ];

    protected $translatable = [
        'name',
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
