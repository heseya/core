<?php

namespace App\Rules;

use App\Models\App;
use App\Services\Contracts\UrlServiceContract;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\App as AppFacade;
use Illuminate\Support\Facades\Storage;

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
        $content = $this->getGoogleProductCategoryFileContent();

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

    private function getGoogleProductCategoryFileContent(): array
    {
        $path = resource_path() . '/storage/google_product_category.txt';

        return file_exists($path) ? file($path) : [];
    }
}
