<?php

namespace App\Dtos;

use Heseya\Dto\Dto;
use Illuminate\Support\Collection;

class BannerMediaDto extends Dto
{
    private string $url;
    private string $title;
    private string $subtitle;
    private Collection $media;

    public static function fromDataArray(array $data): BannerMediaDto
    {
        /** @var Collection<int, mixed> $responsiveMedia */
        $responsiveMedia = $data['responsive_media'];

        return new self(
            url: $data['url'],
            title: $data['title'],
            subtitle: $data['subtitle'],
            media: Collection::make($responsiveMedia)
                ->map(fn ($media) => ResponsiveMediaDto::fromDataArray($media))
        );
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function getMedia(): Collection
    {
        return $this->media;
    }
}
