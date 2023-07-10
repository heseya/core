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
}
