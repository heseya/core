<?php

namespace App\SortColumnTypes;

use Domain\Currency\Currency;
use Illuminate\Validation\Rules\Enum;

final class PriceColumn implements SortableColumn
{
    public static function getColumnName(string $fieldName): string
    {
        return $fieldName;
    }

    public static function getValidationRules(string $fieldName): array
    {
        return ['nullable', new Enum(Currency::class)];
    }
}
