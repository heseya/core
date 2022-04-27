<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BannerDto extends Dto implements InstantiateFromRequest
{
    private string $slug;
    private string $name;
    private bool $active;
    private Collection $banner_media;

    public static function instantiateFromRequest(Request $request): InstantiateFromRequest
    {
        return new self(
            slug: $request->input('slug'),
            name: $request->input('name'),
            active: $request->input('active'),
            banner_media: Collection::make($request->input('banner_media'))
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
