<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BannerDto extends Dto implements InstantiateFromRequest
{
    private string|Missing $slug;
    private string|Missing $name;
    private bool|Missing $active;
    private Collection|Missing $banner_media;

    public static function instantiateFromRequest(Request $request): InstantiateFromRequest
    {
        return new self(
            slug: $request->input('slug', new Missing()),
            name: $request->input('name', new Missing()),
            active: $request->input('active', new Missing()),
            banner_media: Collection::make($request->input('banner_media'))
                ->map(function ($group) {
                    return BannerMediaDto::fromDataArray($group);
                })
        );
    }

    public function getSlug(): string|Missing
    {
        return $this->slug;
    }

    public function getName(): string|Missing
    {
        return $this->name;
    }

    public function isActive(): bool|Missing
    {
        return $this->active;
    }

    public function getBannerMedia(): Collection|Missing
    {
        return $this->banner_media;
    }
}
