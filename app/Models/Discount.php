<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\SearchTypes\DiscountSearch;
use Carbon\Carbon;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperDiscount
 */
class Discount extends Model implements AuditableContract
{
    use HasFactory, Searchable, SoftDeletes, Auditable;

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
     * @OA\Property(
     *   property="starts_at",
     *   type="datetime",
     *   example="2021-09-13T11:11",
     * )
     * @OA\Property(
     *   property="expires_at",
     *   type="datetime",
     *   example="2021-09-13T11:11",
     * )
     */

    protected $fillable = [
        'description',
        'code',
        'discount',
        'type',
        'max_uses',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'type' => DiscountType::class,

    ];

    protected $dates = [
        'starts_at',
        'expires_at',
    ];

    protected array $searchable = [
        'description' => Like::class,
        'code' => Like::class,
        'search' => DiscountSearch::class,
    ];

    public function getUsesAttribute(): int
    {
        return $this->orders->count();
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_discounts');
    }

    public function getAvailableAttribute(): bool
    {
        if ($this->uses >= $this->max_uses) {
            return false;
        }

        $today = Carbon::now();

        if ($this->starts_at !== null && $this->expires_at !== null) {
            return $today >= $this->starts_at && $today <= $this->expires_at;
        }

        if ($this->starts_at !== null) {
            return $today >= $this->starts_at;
        }

        if ($this->expires_at !== null) {
            return $today <= $this->expires_at;
        }

        return true;
    }
}
