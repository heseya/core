<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface GoogleCategoryServiceContract
{
    public function getGoogleProductCategory(string $lang, bool $force = false): Collection;
}
