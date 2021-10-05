<?php

namespace App\Audits\Redactors;

use App\Models\ShippingMethod;
use OwenIt\Auditing\Contracts\AttributeRedactor;

class ShippingMethodRedactor implements AttributeRedactor
{
    /**
     * {@inheritdoc}
     */
    public static function redact($value): string
    {
        /** @var ShippingMethod $shippingMethod */
        $shippingMethod = ShippingMethod::find($value);

        if ($shippingMethod instanceof ShippingMethod) {
            $value = $shippingMethod->name;
        }

        return $value ?? '';
    }
}
