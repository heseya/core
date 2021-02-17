<?php

namespace App\Models;

use App\SearchTypes\ProductSearch;
use App\Traits\Sortable;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema()
 */
class Item extends Model
{
    use SoftDeletes, HasFactory, Searchable, Sortable;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Chain",
     * )
     *
     * @OA\Property(
     *   property="sku",
     *   type="string",
     * )
     *
     * @OA\Property(
     *   property="quantity",
     *   type="string",
     * )
     */

    protected $fillable = [
        'name',
        'sku',
    ];

    protected $searchable = [
        'name' => Like::class,
        'sku' => Like::class,
        'search' => ProductSearch::class,
    ];

    protected array $sortable = [
        'name',
        'sku',
    ];

    public function getQuantityAttribute (): float
    {
        return $this->deposits()->sum('quantity');
    }

    public function deposits (): HasMany
    {
        return $this->hasMany(Deposit::class);
    }
}
