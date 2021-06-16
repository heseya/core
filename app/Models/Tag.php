<?php

namespace App\Models;

use App\SearchTypes\TagSearch;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema()
 */
class Tag extends Model
{
    use HasFactory, Searchable;

    /**
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Sale",
     * ),
     *
     * @OA\Property(
     *   property="color",
     *   type="string",
     *   example="000000",
     * ),
     */
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
