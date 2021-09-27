<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface WebHookServiceContract
{
    public function searchAll(array $attributes): Collection;
}
