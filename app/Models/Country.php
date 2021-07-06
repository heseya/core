<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema()
 *
 * @OA\Property(
 *   property="code",
 *   type="string",
 *   example="PL",
 * )
 *
 * @OA\Property(
 *   property="name",
 *   type="string",
 *   example="Poland",
 * )
 */
class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getKeyName()
    {
        return 'code';
    }
}
