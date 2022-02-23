<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class CommaSeparatedUuids implements Rule
{
    private array $tableExist;

    public function __construct(array $tableExist)
    {
        $this->tableExist = $tableExist;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $fieldName = preg_replace('/\b[\d,.]\b/', '', $attribute);

        $data = [$fieldName => explode(',', $value)];

        $rules = [
            "{$fieldName}" => ['required', 'array', 'max:' . count($this->tableExist)],
        ];

        foreach ($this->tableExist as $key => $table) {
            $rules["{$fieldName}.{$key}"] = ['required', 'uuid', 'exists:' . $table];
        }

        return !Validator::make($data, $rules)->fails();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute must be a valid UUID';
    }
}
