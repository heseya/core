<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Contracts\SettingsServiceContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

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

    public function getSetting(string $name): Setting
    {
        $config = Config::get("settings.{$name}");

        if ($config === null) {
            return Setting::where('name', $name)->firstOrFail();
        }

        $setting = Setting::where('name', $name)->first();

        if ($setting === null) {
            $setting = new Setting($config + [
                'name' => $name,
            ]);
        }

        return $setting;
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function getMinimalPrice(string $name): Money
    {
        $value = Cache::get($name);
        if ($value === null) {
            $value = $this->getSetting($name)->value;
            Cache::put($name, $value);
        }

        return Money::of($value, 'PLN'); // Add multi-currency support
    }
}
