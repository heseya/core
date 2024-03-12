<?php

namespace Database\Factories;

use Domain\Language\Language;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Factories\Factory as IlluminateFactory;
use Illuminate\Support\Facades\App;

abstract class Factory extends IlluminateFactory
{
    protected function withFaker(?string $locale = null): Generator
    {
        $locale ??= Language::query()->find(App::getLocale())?->iso;

        $fakerLocale = match ($locale) {
            'pl' => 'pl_PL',
            'en' => 'en_US',
            config('app.faker_locale') => config('app.faker_locale'),
            default => null,
        };

        if ($fakerLocale === null || $fakerLocale === config('app.faker_locale')) {
            return Container::getInstance()->make(Generator::class);
        } else {
            return Container::getInstance()->make(Generator::class, ['locale' => $locale]);
        }
    }

    public function changeFakerLocale(?string $locale = null): static
    {
        $this->faker = $this->withFaker($locale);
        return $this;
    }
}
