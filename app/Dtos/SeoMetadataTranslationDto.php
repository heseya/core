<?php

namespace App\Dtos;

use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class SeoMetadataTranslationDto extends Dto
{
    private Missing|string|null $title;
    private Missing|string|null $description;
    private array|Missing|null $keywords;
    private bool|Missing $no_index;

    public static function fromDataArray(array $data): self
    {
        return new self(
            title: array_key_exists('title', $data) ? $data['title'] : new Missing(),
            description: array_key_exists('description', $data) ? $data['description'] : new Missing(),
            keywords: array_key_exists('keywords', $data) ? $data['keywords'] : new Missing(),
            no_index: array_key_exists('no_index', $data) ? $data['no_index'] : new Missing(),
        );
    }

    public function getTitle(): Missing|string|null
    {
        return $this->title;
    }

    public function getDescription(): Missing|string|null
    {
        return $this->description;
    }

    public function getKeywords(): array|Missing|null
    {
        return $this->keywords;
    }

    public function getNoIndex(): bool|Missing
    {
        return $this->no_index;
    }
}
