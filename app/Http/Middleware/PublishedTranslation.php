<?php

namespace App\Http\Middleware;

use Closure;
use Domain\Language\Enums\LangFallbackType;
use Domain\Language\Exceptions\TranslationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class PublishedTranslation
{
    public function handle(Request $request, Closure $next, string $model_key): mixed
    {
        $langFallback = $request->input('lang_fallback');
        $fallback = $langFallback ? LangFallbackType::coerce($request->input('lang_fallback')) : null;

        if (!$fallback || (!$fallback->is(LangFallbackType::DEFAULT) && !$fallback->is(LangFallbackType::ANY))) {
            $model = $request->route($model_key);
            if (!in_array(Config::get('language.id'), $model->published)) {
                throw new TranslationException(model: $model);
            }
        }

        return $next($request);
    }
}
