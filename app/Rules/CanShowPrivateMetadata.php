<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class CanShowPrivateMetadata implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function passes($attribute, $value): bool
    {
        return Gate::allows('products.show_metadata_private');
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'To filter by private metadata permission is required.';
    }
}
