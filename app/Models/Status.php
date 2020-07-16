<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class Status extends Model
{
    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
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
     *   property="description",
     *   type="string",
     *   example="Your order has been cancelled!",
     * )
     */
    protected $fillable = [
        'name',
        'color',
        'description',
    ];
}
