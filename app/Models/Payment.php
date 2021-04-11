<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema()
 */
class Payment extends Model
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
     *   property="external_id",
     *   type="string",
     *   example="D2spSDJ21LSA",
     * )
     *
     * @OA\Property(
     *   property="method",
     *   type="string",
     *   example="heseya",
     * )
     *
     * @OA\Property(
     *   property="payed",
     *   type="boolean",
     * )
     *
     * @OA\Property(
     *   property="amount",
     *   type="number",
     *   example=80.92,
     * )
     *
     * @OA\Property(
     *   property="redirect_url",
     *   type="string",
     *   example="https://pay.heseya.com/DS62SA",
     * )
     *
     * @OA\Property(
     *   property="continue_url",
     *   type="string",
     *   example="https://store.heseya.com/done/43SYK1",
     * )
     */
    protected $fillable = [
        'external_id',
        'method',
        'payed',
        'amount',
        'redirect_url',
        'continue_url',
    ];

    protected $casts = [
        'payed' => 'boolean',
        'amount' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
