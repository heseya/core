<?php

namespace App\Models;

use App\SearchTypes\ItemSearch;
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
     *   type="integer",
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
        'search' => ItemSearch::class,
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
