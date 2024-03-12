<?php

declare(strict_types=1);

namespace Domain\Setting\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use Domain\Setting\Dtos\SettingCreateDto;
use Domain\Setting\Dtos\SettingUpdateDto;
use Domain\Setting\Models\Setting;
use Domain\Setting\Services\Contracts\SettingsServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SettingController extends Controller
{
    private SettingsServiceContract $settingsService;

    public function __construct(SettingsServiceContract $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index(Request $request): JsonResponse
    {
        $settings = $this->settingsService->getSettings(
            !Auth::user()?->can('settings.show_hidden'),
        );

        if ($request->has('array')) {
            return Response::json($settings->mapWithKeys(fn ($setting) => [$setting->name => $setting->value]));
        }

        return SettingResource::collection($settings)->response();
    }

    public function show(string $name): JsonResource
    {
        $setting = $this->settingsService->getSetting($name);

        if ($setting->public === false && !Auth::user()?->can('settings.show_hidden')) {
            throw new NotFoundHttpException();
        }

        return SettingResource::make($setting);
    }

    public function store(SettingCreateDto $data): JsonResource
    {
        $setting = Setting::create($data->toArray());

        return SettingResource::make($setting);
    }

    public function update(string $name, SettingUpdateDto $data): JsonResource
    {
        $config = Config::get('settings.' . $name);

        if ($config !== null) {
            $setting = Setting::where('name', $name)->first();

            if ($setting === null) {
                $config = array_replace($config, $data->toArray());
                $setting = Setting::create($config);
            } else {
                $setting->update($data->toArray());
            }

            if (in_array($name, ['minimal_product_price', 'minimal_shipping_price', 'minimal_order_price'], true)) {
                Cache::put($name, $setting->value);
            }
        } else {
            $setting = Setting::where('name', $name)->firstOrFail();
            $setting->update($data->toArray());
        }

        return SettingResource::make($setting);
    }

    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();

        return Response::json(null, 204);
    }
}
