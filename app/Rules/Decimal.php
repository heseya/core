<?php

namespace App\Rules;

class Decimal
{
    public static function defaults(): array
    {
        return [
            'numeric',
            'min:-999999999999',
            'max:999999999999',
        ];
    }
}
