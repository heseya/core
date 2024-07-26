<?php

namespace App\Rules;

use App\Models\Schema as DeprecatedSchema;
use Domain\ProductSchema\Models\Schema\Schema;
use Illuminate\Contracts\Validation\Rule;

readonly class OptionAvailable implements Rule
{
    public function __construct(
        private Schema|DeprecatedSchema $schema,
    ) {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     */
    public function passes($attribute, $value): bool
    {
        $option = $this->schema->options->find($value)?->first();

        if ($option === null) {
            return false;
        }

        return !$option->disabled;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return "This {$this->schema->name} option is not available in given quantity";
    }
}
