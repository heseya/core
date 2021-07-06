<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema ()
 * @mixin IdeHelperOption
 */
class Option extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * ),
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="XL"
     * ),
     * @OA\Property(
     *   property="price",
     *   type="float",
     *   example=3.99,
     *   description="Additional price the customer will have to pay after selecting this option (can be negative)",
     * ),
     * @OA\Property(
     *   property="disabled",
     *   type="boolean",
     *   example=false,
     *   description="Shows if the option has been disabled manually",
     * ),
     * @OA\Property(
     *   property="available",
     *   type="boolean",
     *   example=true,
     *   description="Shows whether the option is available for purchase (in stock and not disabled)",
     * ),
     */
    protected $fillable = [
        'name',
        'price',
        'disabled',
        'schema_id',
    ];

    protected $casts = [
        'price' => 'float',
        'disabled' => 'bool',
        'available' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAvailableAttribute($quantity = 1): bool
    {
        // diwne obejście ale niech bedzie
        $quantity = $quantity ?? 1;

        if ($this->disabled) {
            return false;
        }

        if ($this->items()->count() <= 0) {
            return true;
        }

        // all items must be available for the option to be available
        foreach ($this->items as $item) {
            if ($item->quantity < $quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * @OA\Property(
     *   property="items",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Item"),
     * )
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'option_items');
    }
}
