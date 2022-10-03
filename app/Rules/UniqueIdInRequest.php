<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

class UniqueIdInRequest implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function passes($attribute, $value): bool
    {
        $items = new Collection($value);
        $items = $items->duplicates();

        return $items->isEmpty();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Items\' ids has been duplicated';
    }
}
