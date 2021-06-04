<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @OA\Schema()
 */
class PriceRange extends Model
{
    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="start",
     *   type="number",
     *   example=0.00,
     * )
     */

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'start',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'start' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @OA\Property(
     *   property="prices",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Price"),
     * )
     */
    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'model');
    }
}
