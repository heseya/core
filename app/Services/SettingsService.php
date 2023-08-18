<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Contracts\SettingsServiceContract;
use Domain\Currency\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class SettingsService implements SettingsServiceContract
{
    public function getSettings(bool $publicOnly = false): Collection
    {
        $settings = Setting::orderBy('name')->get();

        /** @var Collection<int, mixed> $configSettings */
        $configSettings = Config::get('settings');

        Collection::make($configSettings)->each(function ($setting, $key) use ($settings): void {
            if (!$settings->contains('name', $key)) {
                $settings->push(new Setting($setting + [
                    'name' => $key,
                ]));
            }
        });

        if ($publicOnly) {
            $settings = $settings->filter(fn ($setting) => $setting->public);
        }

        return $settings;
    }

    public function getSetting(string $name, mixed $default = null): Setting
    {
        $config = Config::get("settings.{$name}");

        if ($config === null && $default === null) {
            return Setting::where('name', $name)->firstOrFail();
        }

        $setting = Setting::where('name', $name)->first();

        if ($setting === null) {
            $setting = new Setting(
                ($config ?? ['value' => $default]) + ['name' => $name]
            );
        }

        return $setting;
    }

    public function getMinimalPrice(string $name = 'minimal_product_price', Currency $currency = Currency::DEFAULT): float
    {
        $value = Cache::get($name);
        if ($value === null) {
            $value = (float) $this->getSetting($name)->value;
            Cache::put($name, $value);
        }

        if ($currency !== Currency::DEFAULT) {
            $name_for_currency = $name . '_' . Str::lower($currency->value);
            $value_for_currency = Cache::get($name_for_currency);
            if ($value_for_currency === null) {
                $value_for_currency = $this->getSetting($name_for_currency, $value)->value;
                Cache::put($name_for_currency, $value_for_currency);
            }

            return $value_for_currency;
        }

        return $value;
    }
}
