<?php

namespace App\Dtos;

use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class SeoMetadataTranslationDto extends Dto
{
    private string|null|Missing $title;
    private string|null|Missing $description;
    private array|null|Missing $keywords;
    private bool|Missing $no_index;

    public static function fromDataArray(array $data): SeoMetadataTranslationDto
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

    public function getKeywords(): Missing|array|null
    {
        return $this->keywords;
    }

    public function getNoIndex(): Missing|bool
    {
        return $this->no_index;
    }
}
