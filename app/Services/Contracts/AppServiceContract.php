<?php

namespace App\Services\Contracts;

use App\Models\App;

interface AppServiceContract
{
    public function info($url): App;

    public function register($url): App;
}
