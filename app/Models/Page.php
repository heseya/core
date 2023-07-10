<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereInIds;
use App\Models\Contracts\SortableContract;
use App\Models\Interfaces\Translatable;
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
class Page extends Model implements AuditableContract, SortableContract, Translatable
{
    use Auditable;
    use Auditable;
    use HasCriteria;
    use HasFactory;
    use HasFactory;
    use HasMetadata;
    use HasSeoMetadata;
    use HasSeoMetadata;
    use HasTranslations;
    use SoftDeletes;
    use SoftDeletes;
    use Sortable;
    use Sortable;

    protected $fillable = [
        'order',
        'name',
        'slug',
        'public',
        'content_html',
        'published',
    ];

    protected array $translatable = [
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
