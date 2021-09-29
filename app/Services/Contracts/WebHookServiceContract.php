<?php

namespace App\Services\Contracts;

use App\Models\WebHook;
use Illuminate\Support\Collection;

interface WebHookServiceContract
{
    public function searchAll(array $attributes): Collection;

    public function create(array $attributes): WebHook;
}
