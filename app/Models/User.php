<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @OA\Schema()
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * @OA\Property(
     *   property="id",
     *   type="integer",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   description="User first and last name.",
     *   example="Johny Mielony",
     * )
     *
     * @OA\Property(
     *   property="email",
     *   type="string",
     *   example="info@hesyea.com",
     * )
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Url to avatar.
     *
     * @return string
     * @OA\Property(
     *   property="avatar",
     *   type="string",
     *   example="//www.gravatar.com/avatar/example.jpg",
     * )
     */
    public function getAvatarAttribute(): string
    {
        return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=mp&s=50x50';
    }
}
