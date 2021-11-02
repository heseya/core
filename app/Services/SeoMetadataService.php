<?php

namespace App\Services;

use App\Models\SeoMetadata;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Support\Facades\Cache;

class SeoMetadataService implements SeoMetadataServiceContract
{
    public function show(): SeoMetadata
    {
        return SeoMetadata::where('global', '=', true)->firstOrFail();
    }

    public function createOrUpdate(array $attributes): SeoMetadata
    {
        $seo = SeoMetadata::firstOrCreate(
            ['global' => true],
            $attributes
        );

        if (!$seo->wasRecentlyCreated) {
            $seo->update($attributes);
        }

        Cache::put('seo.global', $seo);

        return $seo;
    }

    public function create(array $attributes): SeoMetadata
    {
        return SeoMetadata::create(
            $attributes
        );
    }

    public function update(array $attributes, SeoMetadata $seoMetadata): SeoMetadata
    {
        $seoMetadata->update($attributes);
        return $seoMetadata;
    }

    public function delete(SeoMetadata $seoMetadata): void
    {
        $seoMetadata->delete();
    }
}
