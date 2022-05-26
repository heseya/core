<?php

namespace App\Rules;

use App\Services\Contracts\CategoryServiceContract;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\App;

class GoogleProductCategoryExist implements Rule
{
    protected string $error;
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $categoryService = App::make(CategoryServiceContract::class);
        $content = $categoryService->getGoogleProductCategoryFileContent();

        foreach ($content as $item) {
            if ((int) $item === $value) {
                return true;
            }
        }

        return false;
    }

    public function message(): string
    {
        return 'Google product category do not exist';
    }
}
