<?php

namespace App\Services\Contracts;

use App\Dtos\SeoKeywordsDto;
use App\Dtos\SeoMetadataDto;
use App\Models\Model;
use App\Models\SeoMetadata;
use Illuminate\Support\Collection;

interface SeoMetadataServiceContract
{
    public function show(): SeoMetadata;

    public function createOrUpdate(SeoMetadataDto $dto): SeoMetadata;

    public function createOrUpdateFor(Model $model, SeoMetadataDto $dto): void;

    public function update(SeoMetadataDto $dto, SeoMetadata $seoMetadata): SeoMetadata;

    public function delete(SeoMetadata $seoMetadata): void;

    public function checkKeywords(SeoKeywordsDto $dto): Collection;

    public function getGlobalSeo(): SeoMetadata;
}
