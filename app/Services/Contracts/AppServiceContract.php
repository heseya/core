<?php

namespace App\Services\Contracts;

use App\Models\App;

interface AppServiceContract
{
    public function register($url): App;
}
