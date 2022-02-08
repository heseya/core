<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Contracts\SettingsServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class SettingsService implements SettingsServiceContract
{
    public function getSettings($publicOnly = false): Collection
    {
        $settings = Setting::orderBy('name')->get();

        Collection::make(Config::get('settings'))->each(function ($setting, $key) use ($settings): void {
            if (!$settings->contains('name', $key)) {
                $settings->push(Setting::make($setting + [
                    'name' => $key,
                ]));
            }
        });

        if ($publicOnly) {
            $settings->filter(fn ($setting) => $setting->public);
        }

        return $settings;
    }

    public function getSetting(string $name): Setting
    {
        $config = Config::get("settings.${name}");

        if ($config === null) {
            return Setting::where('name', $name)->firstOrFail();
        }

        $setting = Setting::where('name', $name)->first();

        if ($setting === null) {
            $setting = Setting::make($config + [
                'name' => $name,
            ]);
        }

        return $setting;
    }
}
