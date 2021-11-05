<?php

namespace App\Audits\Redactors;

use App\Models\Address;
use OwenIt\Auditing\Contracts\AttributeRedactor;

class AddressRedactor implements AttributeRedactor
{
    /**
     * {@inheritdoc}
     */
    public static function redact($value): string
    {
        /** @var Address $address */
        $address = Address::find($value);

        if ($address instanceof Address) {
            $value = (string) $address;
        }

        return $value ?? '';
    }
}
