<?php

namespace App\Services\Contracts;

use App\Models\SeoMetadata;

interface SeoMetadataServiceContract
{
    public function show(): SeoMetadata;

    public function createOrUpdate(array $attributes): SeoMetadata;
}
