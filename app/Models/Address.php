<?php

namespace App\Models;

use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * @OA\Schema()
 */
class Address extends Model
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
     *   example="Johny Mielony",
     * )
     *
     * @OA\Property(
     *   property="address",
     *   type="string",
     *   example="Gdańska 89/1",
     * )
     *
     * @OA\Property(
     *   property="vat",
     *   type="string",
     *   example="9571099580",
     * )
     *
     * @OA\Property(
     *   property="zip",
     *   type="string",
     *   example="80-200",
     * )
     *
     * @OA\Property(
     *   property="city",
     *   type="string",
     *   example="Bydgoszcz",
     * )
     *
     * @OA\Property(
     *   property="country",
     *   type="string",
     *   example="PL",
     * )
     *
     * @OA\Property(
     *   property="phone",
     *   type="string",
     *   example="+48543234123",
     * )
     */

    protected $fillable = [
        'name',
        'address',
        'vat',
        'zip',
        'city',
        'country',
        'phone',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getPhoneSimpleAttribute()
    {
        $phone = PhoneNumber::make(
            $this->phone,
            $this->country,
        );

        return $phone->formatForMobileDialingInCountry(
            $this->country,
        );
    }
}
