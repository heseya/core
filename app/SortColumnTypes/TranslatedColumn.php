<?php

namespace App\SortColumnTypes;

use Heseya\Sortable\SortableColumn;
use Illuminate\Support\Facades\App;

class TranslatedColumn implements SortableColumn
{
    public static function getColumnName(string $fieldName): string
    {
        $localization = App::getLocale();

        return "{$fieldName}->{$localization}";
    }
}
