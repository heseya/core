<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperOrderSchema
 */
class OrderSchema extends Model
{
    use HasFactory;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * ),
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Size",
     * ),
     * @OA\Property(
     *   property="value",
     *   type="string",
     *   example="XL",
     * ),
     * @OA\Property(
     *   property="price",
     *   type="number",
     *   example="49.99",
     * ),
     */
    protected $fillable = [
        'name',
        'value',
        'price',
        'order_product_id',
    ];
}
