<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Contracts\SettingsServiceContract;
use Illuminate\Support\Collection;
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
}
