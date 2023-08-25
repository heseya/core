<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class WhereIn implements Rule
{
    private string $field;

    public function __construct(private array $fields) {}

    public function passes($attribute, $value): bool
    {
        $this->field = $value;

        if (Str::contains($value, '.')) {
            foreach ($this->fields as $field) {
                $wildcard = Str::endsWith($field, '.*');
                if ($wildcard && preg_match('/^' . Str::remove('*', $field) . '/', $value)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($value, $this->fields) || in_array($value, array_keys($this->fields));
    }

    public function message(): string
    {
        return "You can't sort by {$this->field} field.";
    }
}
