<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait BooleanRules
{
    protected function prepareForValidation(): void
    {
        $data = $this->toArray();

        foreach ($this->booleanFields as $field) {
            if (Arr::has($data, $field)) {
                Arr::set($data, $field, $this->toBoolean(Arr::get($data, $field)));
            } elseif (Str::contains($field, '*')) {
                $before = Str::before($field, '.*');
                $after = Str::after($field, $before);
                $data = $this->toBooleanArrayInPath($before, $after, $data);
            }
        }

        $this->merge($data);
    }

    private function toBoolean(mixed $booleable): bool|int
    {
        if ($booleable === '' || $booleable === null) {
            return true;
        }

        $result = filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $result ?? -1;
    }

    private function toBooleanArrayInPath(string $fieldPath, string $fieldAfter, array $data): array
    {
        // Check if request array has given field
        if (Arr::has($data, $fieldPath)) {
            if (Str::contains($fieldAfter, '*')) {
                $before = Str::before($fieldAfter, '.*');
                $after = Str::after($fieldAfter, $before);

                // If $fieldAfter has .*., then we need to loop through all the values
                if (Str::startsWith($fieldAfter, '.*.')) {
                    foreach (Arr::get($data, $fieldPath) as $key => $value) {
                        $fieldPathTmp = $fieldPath . ('.' . $key . (Str::length($before) > 0 ? '.' . $before : ''));
                        $after = Str::after($fieldAfter, '.*');
                        $data = $this->toBooleanArrayInPath($fieldPathTmp, $after, $data);
                    }
                }
                // If $fieldAfter equals '.*', is the last array
                elseif ($fieldAfter === '.*') {
                    foreach (Arr::get($data, $fieldPath) as $key => $value) {
                        $fieldPathTmp = $fieldPath . '.' . $key;

                        if (Arr::has($data, $fieldPathTmp)) {
                            Arr::set($data, $fieldPathTmp, $this->toBoolean(Arr::get($data, $fieldPathTmp)));
                        }
                    }
                } else {
                    $fieldPath .= $before;
                    $data = $this->toBooleanArrayInPath($fieldPath, $after, $data);
                }
            }
            // Path not contains *, so we can just set the value
            elseif (Str::length($fieldAfter) > 0) {
                $fieldPath .= $fieldAfter;

                if (Arr::has($data, $fieldPath)) {
                    Arr::set($data, $fieldPath, $this->toBoolean(Arr::get($data, $fieldPath)));
                    $this->merge($data);
                }
            }
        }
        return $data;
    }
}
