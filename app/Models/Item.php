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
 * @OA\Schema ()
 * @mixin IdeHelperItem
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
     *   example="K121"
     * )
     *
     * @OA\Property(
     *   property="quantity",
     *   type="float",
     *   example="20",
     * )
     */

    protected $fillable = [
        'name',
        'sku',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'sku' => Like::class,
        'search' => ItemSearch::class,
    ];

    protected array $sortable = [
        'name',
        'sku',
        'created_at',
        'updated_at',
    ];

    public function getQuantityAttribute(): float
    {
        return $this->deposits()->sum('quantity');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }
}
