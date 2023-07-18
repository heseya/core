<?php

namespace App\Rules;

use App\Models\Contracts\SeoContract;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ClassWithSeo implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_subclass_of("App\\Models\\{$value}", SeoContract::class)) {
            $fail("Invalid excluded model '{$value}'");
        }
    }
}
