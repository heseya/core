<?php

namespace Heseya\Sortable;

interface SortableColumn
{
    public static function getColumnName(string $fieldName): string;
}
