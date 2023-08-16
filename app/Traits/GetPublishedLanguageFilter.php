<?php

namespace App\Traits;

use Illuminate\Support\Facades\Config;

trait GetPublishedLanguageFilter
{
    public function getPublishedLanguageFilter(?string $table = null): array
    {
        if (!Config::get('translatable.fallback_locale')) {
            return [$table ? $table . '.published' : 'published' => Config::get('language.id')];
        }
        return [];
    }
}
