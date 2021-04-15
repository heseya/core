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
    public function info($url): App
    {
        $response = Http::get($url);

        if ($response->failed()) {
            throw new Exception('App responsed with error');
        }

        $response = $response->object();

        return App::create([
            'name' => $response->name,
            'url' => $url,
        ]);
    }

    public function register($url): App
    {
        $key = Str::random(64);

        $response = Http::post($url, [
            'key' => $key,
        ]);

        if ($response->failed()) {
            throw new Exception('App responsed with error');
        }

        $response = $response->object();

        return App::create([
            'name' => $response->name,
            'url' => $url,
            'key' => Hash::make($key),
        ]);
    }
}
