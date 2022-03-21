<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperOption
 */
class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'disabled',
        'schema_id',
        'order',
        'available',
    ];

    protected $casts = [
        'price' => 'float',
        'disabled' => 'bool',
        'available' => 'bool',
    ];

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

    public function schema(): BelongsTo
    {
        return $this->belongsTo(Schema::class);
    }
}
