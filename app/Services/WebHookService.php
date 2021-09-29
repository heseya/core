<?php

namespace App\Services;

use App\Models\WebHook;
use App\Services\Contracts\WebHookServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WebHookService implements WebHookServiceContract
{
    public function searchAll(array $attributes): Collection
    {
        return WebHook::search($attributes)->get();
    }

    public function create(array $request): WebHook
    {
        return WebHook::create([
            'name' => $request['name'],
            'url' => $request['url'],
            'secret' => $request['secret'],
            'events' => $request['events'],
            'with_issuer' => $request['with_issuer'],
            'with_hidden' => $request['with_hidden'],
            'model_type' => get_class(Auth::user()),
            'creator_id' => Auth::user()->getKey(),
        ]);
    }
}
