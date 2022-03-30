<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DocumentsBelongToOrder implements Rule
{
    private string $error;

    public function passes($attribute, $value): bool
    {
        $this->error = $value;
        return request()->route('order')->documents()->wherePivot('id', $value)->exists();
    }

    public function message(): string
    {
        return 'Document with id ' . $this->error . ' doesn\'t belong to this order.';
    }
}
