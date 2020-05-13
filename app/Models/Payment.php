<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class Payment extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_PAYED = 1;
    const STATUS_FAILURE = 2;

    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="method",
     *   type="string",
     *   example="heseya",
     * )
     *
     * @OA\Property(
     *   property="status",
     *   type="string",
     *   example="payed",
     * )
     *
     * @OA\Property(
     *   property="currency",
     *   type="string",
     *   example="PLN",
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
        'status',
        'currency',
        'amount',
        'redirect_url',
        'continue_url',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
