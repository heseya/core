<?php

namespace App\SortColumnTypes;

use Illuminate\Support\Facades\App;

final class TranslatedColumn implements SortableColumn
{
    public static function getColumnName(string $fieldName): string
    {
        $localization = App::getLocale();

        return "{$fieldName}->{$localization}";
    }

    public static function getValidationRules(string $fieldName): array
    {
        return [];
    }

    public static function useRawOrderBy(): bool
    {
        return false;
    }
}
