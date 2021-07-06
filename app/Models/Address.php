<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * @OA\Schema ()
 * @mixin IdeHelperAddress
 */
class Address extends Model
{
    use HasFactory;

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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function countryModel(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }

    public function getPhoneSimpleAttribute(): string
    {
        $phone = PhoneNumber::make(
            $this->phone,
            $this->country,
        );

        return $phone->formatForMobileDialingInCountry($this->country);
    }
}
