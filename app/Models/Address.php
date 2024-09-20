<?php

namespace App\Models;

use Domain\Organization\Models\Organization;
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
        'company_name',
    ];

    public function orders(): HasMany
    {
        return $this
            ->hasMany(Order::class, 'billing_address_id')
            ->orWhere('shipping_address_id', $this->id);
    }

    public function countryModel(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }

    public function getCountryNameAttribute(): ?string
    {
        $name = Cache::get('countryName.' . $this->country);

        if ($name === null) {
            $name = $this->countryModel?->name;
            Cache::put('countryName.' . $this->country, $name);
        }

        return $name ?? $this->country;
    }

    public function getPhoneSimpleAttribute(): ?string
    {
        if ($this->phone && $this->country) {
            $phone = new PhoneNumber(
                $this->phone,
                $this->country,
            );

            return $phone->formatForMobileDialingInCountry($this->country);
        }

        return null;
    }

    /**
     * @return HasMany<Organization>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'billing_address_id', 'id');
    }
}
