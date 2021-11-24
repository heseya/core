<?php

namespace App\Rules;

use App\Models\App;
use App\Services\Contracts\UrlServiceContract;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\App as AppFacade;

class AppUniqueUrl implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        /** @var UrlServiceContract $urlService */
        $urlService = AppFacade::make(UrlServiceContract::class);
        $urls = $urlService->equivalentNormalizedUrls($value, true);

        $apps = App::query()->where('url', 'like', $urls[0] . '%')
            ->orWhere('url', 'like', $urls[1] . '%');

        return !$apps->exists();
    }

    public function message(): string
    {
        return 'App with url :value is already installed';
    }
}
