<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface EventServiceContract
{
    public function index(): Collection;
}
