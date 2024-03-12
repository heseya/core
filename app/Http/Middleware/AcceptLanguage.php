<?php

namespace App\Http\Middleware;

use App\Traits\GetPreferredLanguage;
use Closure;
use Domain\Language\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class AcceptLanguage
{
    use GetPreferredLanguage;

    public function handle(Request $request, Closure $next): mixed
    {
        $language = $this->getPreferredLanguage(
            $request->header('Accept-Language'),
            Language::query()->where('hidden', false)->get(),
        );

        Config::set('language.model', $language);
        Config::set('language.id', $language->getKey());
        Config::set('language.iso', $language->iso);

        App::setLocale($language->getKey());

        return $next($request);
    }
}
