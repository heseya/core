<?php

namespace App\Services;

use App\Models\WebHook;
use App\Services\Contracts\WebHookServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class WebHookService implements WebHookServiceContract
{
    public function searchAll(array $attributes, ?string $sort): LengthAwarePaginator
    {
        return WebHook::searchByCriteria($attributes)
            ->sort($sort)
            ->paginate(Config::get('pagination.per_page'));
    }

    public function create(array $request): WebHook
    {
        return WebHook::create([
            'name' => $request['name'] ?? null,
            'url' => $request['url'],
            'secret' => $request['secret'] ?? null,
            'events' => $request['events'],
            'with_issuer' => $request['with_issuer'],
            'with_hidden' => $request['with_hidden'],
            'model_type' => Auth::user() ? Auth::user()->getMorphClass() : null,
            'creator_id' => Auth::user()?->getKey(),
        ]);
    }

    public function update(WebHook $webHook, array $attributes): WebHook
    {
        $webHook->update($attributes);

        return $webHook;
    }

    public function delete(WebHook $webHook): void
    {
        $webHook->delete();
    }
}
