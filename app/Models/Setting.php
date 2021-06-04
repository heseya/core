<?php

namespace App\Models;

/**
 * @OA\Schema()
 */
class Setting extends Model
{
    /**
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="setting-name",
     * )
     *
     * @OA\Property(
     *   property="value",
     *   type="string",
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     *
     * @OA\Property(
     *   property="permanent",
     *   type="boolean",
     * )
     */

    protected $fillable = [
        'name',
        'value',
        'public',
    ];

    protected $casts = [
        'public' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getPermanentAttribute(): bool
    {
        return config('settings.' . $this->name) !== null;
    }
}
