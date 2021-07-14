<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperPasswordReset
 */
class PasswordReset extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @OA\Property(
     *   property="email",
     *   type="string",
     *   example="info@hesyea.com",
     * )
     *
     * @OA\Property(
     *   property="token",
     *   type="string",
     * )
     */

    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
