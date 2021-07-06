<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\SearchTypes\DiscountSearch;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema ()
 * @mixin IdeHelperDiscount
 */
class Discount extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="code",
     *   type="string",
     *   example="83734AE",
     * )
     *
     * @OA\Property(
     *   property="description",
     *   type="string",
     *   example="Balck Weekend 2021",
     * )
     *
     * @OA\Property(
     *   property="type",
     *   type="number",
     *   example="0",
     * )
     *
     * @OA\Property(
     *   property="discount",
     *   type="float",
     *   example="50",
     * )
     *
     * @OA\Property(
     *   property="uses",
     *   type="float",
     *   example="41",
     * )
     *
     * @OA\Property(
     *   property="max_uses",
     *   type="float",
     *   example="100",
     * )
     *
     * @OA\Property(
     *   property="available",
     *   type="boolean",
     *   example="true",
     * )
     */
    protected $fillable = [
        'description',
        'code',
        'discount',
        'type',
        'max_uses',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected array $searchable = [
        'description' => Like::class,
        'code' => Like::class,
        'search' => DiscountSearch::class,
    ];

    public function getUsesAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getAvailableAttribute(): bool
    {
        return $this->max_uses > $this->uses;
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_discounts');
    }
}
