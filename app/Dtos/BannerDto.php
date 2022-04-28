<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class BannerDto extends Dto implements InstantiateFromRequest
{
    private string $slug;
    private string $name;
    private bool $active;
    private Collection $banner_media;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        /** @var Collection<int, mixed> $bannerMedias */
        $bannerMedias = $request->input('banner_media');

        return new self(
            slug: $request->input('slug'),
            name: $request->input('name'),
            active: $request->input('active'),
            banner_media: Collection::make($bannerMedias)
                ->map(function ($group) {
                    return BannerMediaDto::fromDataArray($group);
                })
        );
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getBannerMedia(): Collection
    {
        return $this->banner_media;
    }
}
