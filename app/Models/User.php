<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * @OA\Schema ()
 * 
 * @mixin IdeHelperUser
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use HasApiTokens,
        Notifiable,
        Authenticatable,
        Authorizable,
        CanResetPassword,
        MustVerifyEmail,
        HasFactory;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Url to avatar.
     *
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
