<?php

namespace App\Services;

use App\Dtos\SeoMetadataDto;
use App\Models\SeoMetadata;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Support\Facades\Cache;

class SeoMetadataService implements SeoMetadataServiceContract
{
    public function show(): SeoMetadata
    {
        return SeoMetadata::where('global', '=', true)->firstOrFail();
    }

    public function createOrUpdate(SeoMetadataDto $dto): SeoMetadata
    {
        $seo = SeoMetadata::firstOrCreate(
            ['global' => true],
            $dto->toArray()
        );

        if (!$seo->wasRecentlyCreated) {
            $seo->update($dto->toArray());
        }

        Cache::put('seo.global', $seo);

        return $seo;
    }

    public function create(SeoMetadataDto $dto): SeoMetadata
    {
        return SeoMetadata::create(
            $dto->toArray()
        );
    }

    public function update(SeoMetadataDto $dto, SeoMetadata $seoMetadata): SeoMetadata
    {
        $seoMetadata->update($dto->toArray());
        return $seoMetadata;
    }

    public function delete(SeoMetadata $seoMetadata): void
    {
        $seoMetadata->delete();
    }
}
