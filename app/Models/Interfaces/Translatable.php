<?php

namespace App\Models\Interfaces;

interface Translatable
{
    public function getTranslation(string $key, string $locale, bool $useFallbackLocale = true): mixed;

    public function getTranslations(string $key = null, array $allowedLocales = null): array;

    public function setTranslation(string $key, string $locale, $value): self;

    public function setTranslations(string $key, array $translations): self;

    public function hasTranslation(string $key, string $locale = null): bool;
}
