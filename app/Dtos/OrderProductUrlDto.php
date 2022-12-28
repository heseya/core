<?php

namespace App\Dtos;

use Heseya\Dto\Dto;

class OrderProductUrlDto extends Dto
{
    private string $name;
    private ?string $url;

    public static function init(string $name, ?string $url): self
    {
        return new self(
            name: $name,
            url: $url,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
