<?php

namespace App\Models;

use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCountry
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

    public function getKeyName(): string
    {
        return 'code';
    }

    public function shippingMethods(): BelongsToMany
    {
        return $this->belongsToMany(
            ShippingMethod::class,
            'shipping_method_country',
            'country_code',
            'shipping_method_id',
            'code',
            'id',
            'countries',
        );
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(
            Address::class,
            'code',
            'country',
        );
    }
}
