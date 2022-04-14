<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BannerDto extends Dto implements InstantiateFromRequest
{
    private string $slug;
    private string $url;
    private string $name;
    private bool $active;
    private Collection $responsive_media;

    public static function instantiateFromRequest(Request $request): InstantiateFromRequest
    {
        return new self(
            slug: $request->input('slug'),
            url: $request->input('url'),
            name: $request->input('name'),
            active: $request->input('active'),
            responsive_media: Collection::make($request->input('responsive_media'))
                ->map(function ($group) {
                    return Collection::make($group)->map(fn ($media) => ResponsiveMediaDto::fromDataArray($media));
                })
        );
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getResponsiveMedia(): Collection
    {
        return $this->responsive_media;
    }
}
