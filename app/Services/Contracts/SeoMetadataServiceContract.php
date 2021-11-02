<?php

namespace App\Services\Contracts;

use App\Models\SeoMetadata;

interface SeoMetadataServiceContract
{
    public function show(): SeoMetadata;

    public function createOrUpdate(array $attributes): SeoMetadata;

    public function create(array $attributes): SeoMetadata;

    public function update(array $attributes, SeoMetadata $seo): SeoMetadata;

    public function delete(SeoMetadata $seoMetadata): void;
}
