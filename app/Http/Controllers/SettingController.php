<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\SettingControllerSwagger;
use App\Http\Requests\SettingCreateRequest;
use App\Http\Requests\SettingIndexRequest;
use App\Http\Requests\SettingUpdateRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller implements SettingControllerSwagger
{

    public function index(): JsonResource
    {
        $settings = Setting::all();

        $config = collect(config('settings'))
            ->each(function($setting, $key) use ($settings) {
                if (!$settings->contains('name', $key)) {
                    $settings->push(Setting::make($setting + [
                        'name' => $key,
                    ]));
                }
            });

        if (!Auth::check()) {
            return SettingResource::collection(
                $settings->filter(fn($setting) => $setting->public),
            );
        }

        return SettingResource::collection($settings);
    }

    public function show(string $name): JsonResource
    {
        $config = config("settings.$name", null);
        
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

        if ($setting->public === false && !Auth::check()) {
            abort(404);
        }

        return SettingResource::make($setting);
    }

    public function store(SettingCreateRequest $request): JsonResource
    {
        $setting = Setting::create($request->validated());

        return SettingResource::make($setting);
    }

    public function update(string $name, SettingUpdateRequest $request): JsonResource
    {
        $config = config("settings.$name");

        if ($config !== null) {
            $setting = Setting::where('name', $name)->first();

            if ($setting === null) {
                // Coppy setting from config to db

                $setting = Setting::create($config + [
                    'name' => $name,
                ]);
            }
        } else {
            $setting = Setting::where('name', $name)->firstOrFail();
        }

        $setting->update($request->validated());

        return SettingResource::make($setting);
    }

    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();

        return response()->json(null, 204);
    }
}
