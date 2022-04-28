<?php

namespace App\Dtos;

use Heseya\Dto\Dto;
use Illuminate\Support\Collection;

class BannerMediaDto extends Dto
{
    private ?string $url;
    private ?string $title;
    private ?string $subtitle;
    private Collection $media;

    public static function fromDataArray(array $data): BannerMediaDto
    {
        return new self(
            url: $data['url'] ?? null,
            title: $data['title'] ?? null,
            subtitle: $data['subtitle'] ?? null,
            media: Collection::make($data['media'])
                ->map(fn ($media) => ResponsiveMediaDto::fromDataArray($media))
        );
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getMedia(): Collection
    {
        return $this->media;
    }
}
