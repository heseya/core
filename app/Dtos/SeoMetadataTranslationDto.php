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
