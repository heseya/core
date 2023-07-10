<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Traits\GetPreferredLanguage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class AcceptLanguage
{
    use GetPreferredLanguage;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     */
    public function handle($request, Closure $next): mixed
    {
        $language = $this->getPreferredLanguage(
            $request->header('Accept-Language'),
            Language::where('hidden', false)->get(),
        );

        Config::set('language.model', $language);
        Config::set('language.id', $language->getKey());
        Config::set('language.iso', $language->iso);

        App::setLocale($language->getKey());

        return $next($request);
    }
}
