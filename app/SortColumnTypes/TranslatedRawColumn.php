<?php

namespace App\SortColumnTypes;

use Illuminate\Support\Facades\App;

final class TranslatedRawColumn implements SortableColumn
{
    public static function getColumnName(string $fieldName): string
    {
        $localization = App::getLocale();

        return "(JSON_UNQUOTE(JSON_EXTRACT({$fieldName}, '$.\"{$localization}\"')) COLLATE utf8mb4_0900_ai_ci)";
    }

    public static function getValidationRules(string $fieldName): array
    {
        return [];
    }

    public static function useRawOrderBy(): bool
    {
        return true;
    }
}
