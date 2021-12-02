<?php

namespace App\Models;

use App\SearchTypes\TagSearch;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperTag
 */
class Tag extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'color',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'color' => Like::class,
        'search' => TagSearch::class,
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tags');
    }
}
