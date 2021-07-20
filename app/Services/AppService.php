<?php

namespace App\Services;

use App\Models\App;
use App\Services\Contracts\AppServiceContract;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AppService implements AppServiceContract
{
    public function register($url): App
    {
        $key = Str::random(64);
        $app = App::create([
            'url' => $url,
            'key' => Hash::make($key),
        ]);

        try {
            $response = Http::post($url, [
                'id' => $app->getKey(),
                'key' => $key,
            ]);

            if ($response->failed()) {
                throw new Exception();
            }

            $response = $response->object();

            if (!$this->isValidRegisterResponse($response)) {
                throw new Exception();
            }
        } catch (Exception $exception) {
            $app->delete();
            throw new Exception('App responded with error');
        }

        $app->update([
            'name' => $response->name,
        ]);

        return $app;
    }

    protected function isValidRegisterResponse($response): bool
    {
        return isset($response->name);
    }
}
