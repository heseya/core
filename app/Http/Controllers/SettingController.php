<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\SettingControllerSwagger;
use App\Http\Requests\SettingCreateRequest;
use App\Http\Requests\SettingUpdateRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Services\Contracts\SettingsServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller implements SettingControllerSwagger
{
    private SettingsServiceContract $settingsService;

    public function __construct(SettingsServiceContract $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index(Request $request): JsonResponse
    {
        $settings = $this->settingsService->getSettings(!Auth::check());

        if ($request->has('array')) {
            return response()->json($settings->mapWithKeys(function ($setting) {
                return [$setting->name => $setting->value];
            }));
        }

        return SettingResource::collection($settings)->response();
    }

    public function show(string $name): JsonResource
    {
        $setting = $this->settingsService->getSetting($name);

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
        $config = config('settings.' . $name);

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
