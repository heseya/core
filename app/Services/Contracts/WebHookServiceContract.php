<?php

namespace App\Services\Contracts;

use App\Models\WebHook;
use Illuminate\Support\Collection;

interface WebHookServiceContract
{
    public function searchAll(array $attributes, ?string $sort): Collection;

    public function create(array $attributes): WebHook;

    public function update(WebHook $webHook, array $attributes): WebHook;

    public function delete(WebHook $webHook): void;
}
