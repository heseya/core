<?php

namespace App\Rules;

use App\Models\Language;
use Illuminate\Contracts\Validation\Rule;

class Translations implements Rule
{
    private string $error;

    public function __construct(
        private readonly array $fields,
    ) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     */
    public function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            return $this->failWithError(':attribute is not a properly structured translation object');
        }

        foreach ($value as $language => $translations) {
            if (!Language::query()->where('id', '=', $language)->exists()) {
                return $this->failWithError("Language {$language} in :attribute doesn't exist");
            }

            if (!is_array($translations)) {
                return $this->failWithError(":attribute.{$language} should contain appropriate fields");
            }

            if (count(array_intersect($this->fields, array_keys($translations))) < 1) {
                return $this->failWithError(":attribute.{$language} should contain appropriate fields");
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->error;
    }

    private function failWithError(string $error): bool
    {
        $this->error = $error;

        return false;
    }
}
