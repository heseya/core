<?php

namespace App\Services\Contracts;

interface CategoryServiceContract
{
    public function getGoogleProductCategory(string $lang = 'en-US', bool $force = false): array;

    public function getGoogleProductCategoryFileContent(string $lang = 'en-US'): array;

    public function getFromGoogleServer(string $lang = 'en-US'): array;
}
