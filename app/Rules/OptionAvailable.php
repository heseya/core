<?php

namespace App\Rules;

use App\Models\Schema;
use Illuminate\Contracts\Validation\Rule;

class OptionAvailable implements Rule
{
    private Schema $schema;
    private float $quantity;

    public function __construct(Schema $schema, float $quantity)
    {
        $this->schema = $schema;
        $this->quantity = $quantity;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $option = $this->schema->options->find($value);

        if ($option === null) {
            return false;
        }

        return $option->getAvailableAttribute($this->quantity);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return ':attribute is not available';
    }
}
