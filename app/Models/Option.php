<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperOption
 */
class Option extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'price',
        'disabled',
        'schema_id',
        'order',
    ];

    protected $translatable = [
        'name',
    ];

    protected $casts = [
        'price' => 'float',
        'disabled' => 'bool',
        'available' => 'bool',
    ];

    public function getAvailableAttribute($quantity = 1): bool
    {
        // diwne obejście ale niech bedzie
        $quantity = $quantity ?? 1;

        if ($this->disabled) {
            return false;
        }

        if ($this->items->count() <= 0) {
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
