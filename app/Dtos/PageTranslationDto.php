<?php

namespace App\Dtos;

use Heseya\Dto\Dto;

class PageTranslationDto extends Dto
{
    private string $name;
    private string $content_html;

    public static function fromParams(string $name, string $content_html)
    {
        return new self(
            name: $name,
            content_html: $content_html,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContentHtml(): string
    {
        return $this->content_html;
    }
}
