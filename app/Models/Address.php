<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * @mixin IdeHelperAddress
 */
class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'vat',
        'zip',
        'city',
        'country',
        'phone',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function countryModel(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }

    public function getCountryNameAttribute(): string
    {
        $name = Cache::get('countryName.' . $this->country);

        if ($name === null) {
            $name = $this->countryModel?->name;
            Cache::put('countryName.' . $this->country, $name);
        }

        return $name;
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
