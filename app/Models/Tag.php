<?php

namespace App\Models;

use App\Criteria\TagSearch;
use App\Criteria\WhereInIds;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperTag
 */
class Tag extends Model
{
    use HasFactory;
    use HasCriteria;

    protected $fillable = [
        'id',
        'name',
        'color',
    ];

    protected array $criteria = [
        'name' => Like::class,
        'color' => Like::class,
        'search' => TagSearch::class,
        'ids' => WhereInIds::class,
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tags');
    }
}
