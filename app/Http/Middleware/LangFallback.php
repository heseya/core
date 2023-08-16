<?php

namespace App\Http\Middleware;

use Closure;
use Domain\Language\Enums\LangFallbackType;
use Domain\Language\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class LangFallback
{
    public function handle(Request $request, Closure $next): mixed
    {
        $fallback = $request->input('lang_fallback');

        match ($fallback) {
            LangFallbackType::DEFAULT->value => $this->configureFallback(true, false, 'null'),
            LangFallbackType::ANY->value => $this->configureFallback(true, true, 'any'),
            default => $this->configureFallback(false, false),
        };

        return $next($request);
    }

    private function configureFallback(bool $default, bool $fallback_any, ?string $iso = null): void
    {
        $fallback_locale = $default ? Language::where('default', true)->first()->getKey() : null;
        Config::set('translatable.fallback_locale', $fallback_locale);

        Config::set('translatable.fallback_any', $fallback_any);

        if ($iso) {
            Config::set('language.iso', $iso);
        }
    }
}
