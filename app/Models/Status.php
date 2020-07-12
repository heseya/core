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
     *   example="Gotowe",
     * )
     *
     * @OA\Property(
     *   property="color",
     *   type="string",
     *   example="",
     * )
     */
    protected $fillable = [
        'name',
        'color',
        'description',
    ];
}
