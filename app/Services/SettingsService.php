<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Contracts\SettingsServiceContract;
use Illuminate\Support\Collection;

class SettingsService implements SettingsServiceContract
{
    public function getSettings($publicOnly = false): Collection
    {
        $settings = Setting::all();

        collect(config('settings'))->each(function ($setting, $key) use ($settings) {
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
        $config = config("settings.$name");

        if ($config === null) {
            $setting = Setting::where('name', $name)->firstOrFail();
        } else {
            $setting = Setting::where('name', $name)->first();

            if ($setting === null) {
                $setting = Setting::make($config + [
                    'name' => $name,
                ]);
            }
        }

        return $setting;
    }
}
