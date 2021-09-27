<?php

namespace App\Services;

use App\Models\WebHook;
use App\Services\Contracts\WebHookServiceContract;
use Illuminate\Support\Collection;

class WebHookService implements WebHookServiceContract
{
    public function searchAll(array $attributes): Collection
    {
        return WebHook::search($attributes)->get();
    }
}
