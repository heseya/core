<?php

namespace App;

use App\Order;
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
     *   property="redirectUrl",
     *   type="string",
     *   example="https://pay.heseya.com/DS62SA",
     * )
     *
     * @OA\Property(
     *   property="continueUrl",
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
        'redirectUrl',
        'continueUrl',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
