<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema ()
 * @mixin IdeHelperStatus
 */
class Status extends Model
{
    use HasFactory;

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
     *   example="Cancel",
     * )
     *
     * @OA\Property(
     *   property="color",
     *   type="string",
     *   example="8f022c",
     * )
     *
     * @OA\Property(
     *   property="cancel",
     *   type="boolean",
     * )
     *
     * @OA\Property(
     *   property="description",
     *   type="string",
     *   example="Your order has been cancelled!",
     * )
     */
    protected $fillable = [
        'name',
        'color',
        'cancel',
        'description',
        'order',
    ];

    protected $casts = [
        'cancel' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
