<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class DocumentsBelongToOrder implements Rule
{
    private string $error;

    public function passes($attribute, $value): bool
    {
        $this->error = $value;
        $documents = request()->route('order')->documents->pluck('pivot.id');
        return $documents->contains($value);
    }

    public function message(): string
    {
        return 'Document with id ' . $this->error . ' doesn\'t belong to this order.';
    }
}
