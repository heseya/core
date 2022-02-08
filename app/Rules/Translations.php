<?php

namespace App\Rules;

use App\Models\App;
use App\Models\Language;
use App\Services\Contracts\UrlServiceContract;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\App as AppFacade;

class Translations implements Rule
{
    private string $error;

    public function __construct(
        private array $fields,
    ) {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            return $this->failWithError(':attribute is not a properly structured translation object');
        }

        foreach ($value as $language => $translations) {
            if (Language::find($language) === null) {
                return $this->failWithError("Language {$language} in :attribute doesn't exist");
            }

            if (!is_array($translations)) {
                return $this->failWithError(":attribute.{$language} should contain appropriate fields");
            }

            if (count(array_intersect($this->fields, $translations)) > 0) {
                return $this->failWithError(":attribute.{$language} should contain appropriate fields");
            }
        }

        return true;
    }

    private function failWithError(string $error): bool
    {
        $this->error = $error;

        return false;
    }

    public function message(): string
    {
        return $this->error;
    }
}
