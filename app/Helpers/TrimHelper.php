<?php

namespace App\Helpers;

class TrimHelper
{
    public static function trim(string $value, ?string $charlist = null): string
    {
        if ($charlist === null) {
            $trimDefaultCharacters = " \n\r\t\v\0";

            return trim(preg_replace('~[\s\x{FEFF}\x{200B}\x{200E}' . $trimDefaultCharacters . ']{2,}~u', ' ', $value) ?? $value);
        }

        return trim($value, $charlist);
    }

    /**
     * @param array<int|string, string|null> $array
     *
     * @return array<int|string, string|null>
     */
    public static function trimArrayValues(array $array, ?string $charlist = null): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = $value ? self::trim($value, $charlist) : $value;
        }

        return $array;
    }
}
