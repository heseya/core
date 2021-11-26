<?php

namespace App\Services\Contracts;

use App\Dtos\SeoMetadataDto;
use App\Models\SeoMetadata;

interface SeoMetadataServiceContract
{
    public function show(): SeoMetadata;

    public function createOrUpdate(SeoMetadataDto $dto): SeoMetadata;

    public function create(SeoMetadataDto $dto): SeoMetadata;

    public function update(SeoMetadataDto $dto, SeoMetadata $seo): SeoMetadata;

    public function delete(SeoMetadata $seoMetadata): void;
}
