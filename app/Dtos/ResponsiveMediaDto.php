<?php

namespace App\Dtos;

use Heseya\Dto\Dto;

class ResponsiveMediaDto extends Dto
{
    private int $min_screen_width;
    private string $media;

    public static function fromDataArray(array $data): ResponsiveMediaDto
    {
        return new self(
            min_screen_width: $data['min_screen_width'],
            media: $data['media'],
        );
    }

    public function getMinScreenWidth(): int
    {
        return $this->min_screen_width;
    }

    public function getMedia(): string
    {
        return $this->media;
    }
}
