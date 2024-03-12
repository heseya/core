<?php

declare(strict_types=1);

namespace Domain\Banner\Resources;

use App\Http\Resources\Resource;
use App\Traits\MetadataResource;
use Domain\Banner\Models\BannerMedia;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

final class BannerResource extends Resource
{
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'active' => $this->resource->active,
            'banner_media' => BannerMediaResource::collection(
                // If the request has the with_translations parameter set to true,
                // then return all the bannerMedia,
                // otherwise return only the bannerMedia that have the current locale in the published array
                $request->boolean(
                    'with_translations',
                ) ? $this->resource->bannerMedia : $this->resolveBannerMediaByLocale(),
            ),
        ], $this->metadataResource('banners.show_metadata_private'));
    }

    /**
     * @return Collection<int, BannerMedia>
     */
    private function resolveBannerMediaByLocale(): Collection
    {
        return $this->resource->bannerMedia->filter(fn (BannerMedia $bannerMedia) => is_array($bannerMedia->published) && in_array(App::getLocale(), $bannerMedia->published, true));
    }
}
