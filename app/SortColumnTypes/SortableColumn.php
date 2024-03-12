<?php

namespace App\SortColumnTypes;

interface SortableColumn
{
    public static function getColumnName(string $fieldName): string;

    public static function getValidationRules(string $fieldName): array;

    public static function useRawOrderBy(): bool;
}
