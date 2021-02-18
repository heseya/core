<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema()
 */
class Option extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="name",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="value",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="disabled",
     *   type="boolean",
     * ),
     */
    protected $fillable = [
        'name',
        'value',
        'disabled',
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'option_items');
    }
}
