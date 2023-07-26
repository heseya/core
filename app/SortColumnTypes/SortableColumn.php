<?php

namespace App\SortColumnTypes;

interface SortableColumn
{
    public static function getColumnName(string $fieldName): string;
}
