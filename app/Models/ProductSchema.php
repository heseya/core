<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema()
 */
class ProductSchema extends Model
{
    use SoftDeletes, HasFactory;

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
     *   property="type",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="required",
     *   type="boolean",
     * )
     */

    protected $fillable = [
        'name',
        'type',
        'required',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'required' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @OA\Property(
     *   property="schema_items",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/ProductSchemaItem"),
     * )
     */
    public function schemaItems(): HasMany
    {
        return $this->hasMany(ProductSchemaItem::class)->with('item');
    }
}
