<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BannerDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private Missing|string|null $slug;
    private Missing|string|null $name;
    private bool|Missing|null $active;
    private Collection|Missing $banner_media;
    private array|Missing $metadata;

    public static function instantiateFromRequest(Request $request): self
    {
        /** @var Collection<int, mixed> $bannerMedias */
        $bannerMedias = $request->input('banner_media');

        return new self(
            slug: $request->input('slug', new Missing()),
            name: $request->input('name', new Missing()),
            active: $request->input('active', new Missing()),
            banner_media: Collection::make($bannerMedias)
                ->map(fn ($group) => BannerMediaDto::fromDataArray($group)),
            metadata: self::mapMetadata($request),
        );
    }

    public function getBannerMedia(): Collection|Missing
    {
        return $this->banner_media;
    }
}
