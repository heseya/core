<?php

namespace App\Models;

use App\Models\Interfaces\Translatable;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Models\Contracts\SortableContract;
use App\Traits\HasMetadata;
use App\Traits\HasSeoMetadata;
use App\Traits\Sortable;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperPage
 */
class Page extends Model implements AuditableContract, Translatable
class Page extends Model implements AuditableContract, SortableContract
{
    use HasFactory, Sortable, Auditable, SoftDeletes, HasSeoMetadata, HasTranslations;
    use Auditable;
    use HasCriteria;
    use HasFactory;
    use HasMetadata;
    use HasSeoMetadata;
    use SoftDeletes;
    use Sortable;

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

    protected array $criteria = [
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_page',
        );
    }
}
