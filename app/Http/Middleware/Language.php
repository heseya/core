<?php

namespace App\Http\Middleware;

use App\Models\Language as LanguageModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     */
    public function handle($request, Closure $next): mixed
    {
        $language = $this->getPreferredLanguage($request, LanguageModel::all());

        App::setLocale($language->getKey());

        return $next($request);
    }

    protected function getPreferredLanguage(string $languageHeader, Collection $languages)
    {
        // Extract language codes from $languageHeader in priority order
        $headerLanguages = Collection::make();

        foreach(explode(',', $languageHeader) as $language) {
            if (strpos($language, ';') === false) {
                $priority = 1;
            } else {
                $priority = explode('=', $language)[1];
                $language = explode(';', $language)[0];
            }

            $group = $headerLanguages->get($priority, []);
            $group[] = $language;
            $headerLanguages->put($priority, $group);
        }

        $headerLanguages = $headerLanguages->sortKeysDesc()->flatten();

        // Get first matching language
        $languageCodes = $languages->map(fn (LanguageModel $language) => $language->code);

        foreach($headerLanguages as $code) {
            if ($languageCodes->contains($code)) {
                return $languages->firstWhere('code', $code);
            }
        }

        return $languages->firstWhere('default', true);
    }

}
