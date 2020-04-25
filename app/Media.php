<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 */
class Media extends Model
{
    const OTHER = 0;
    const PHOTO = 1;
    const VIDEO = 2;

    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="type",
     *   type="string",
     *   example="photo",
     * )
     *
     * @OA\Property(
     *   property="url",
     *   type="string",
     *   example="https://cdn.heseya.com/image.jpg"
     * )
     */

    protected $fillable = [
        'type',
        'url',
    ];
}
