<?php

namespace App\Audits\Redactors;

use App\Models\Address;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Contracts\AttributeRedactor;

class AddressRedactor implements AttributeRedactor
{
    public static function redact(mixed $value): string
    {
        $cache = Cache::get("address.{$value}");

        if ($cache !== null) {
            Cache::forget("address.{$value}");

            return $cache;
        }

        $address = Address::find($value);

        if ($address instanceof Address) {
            $value = (string) $address;
        }

        return $value ?? '';
    }
}
