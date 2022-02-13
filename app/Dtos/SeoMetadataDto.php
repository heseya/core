<?php

namespace App\Dtos;

use App\Enums\TwitterCardType;
use App\Http\Requests\SeoMetadataRequest;
use App\Http\Requests\SeoMetadataRulesRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class SeoMetadataDto extends Dto
{
    /** @var array<string, SeoMetadataTranslationDto> */
    private array $translations;
    /** @var array<string> */
    private array $published;
    private string|null|Missing $twitter_card;
    private string|null|Missing $og_image;
    private string|null|Missing $model_id;
    private string|null|Missing $model_type;

    public static function fromFormRequest(SeoMetadataRulesRequest|SeoMetadataRequest $request): self
    {
        $seo = $request->has('seo') ? 'seo.' : '';

        $translations = array_map(
            fn ($data) => SeoMetadataTranslationDto::fromDataArray($data),
            $request->input($seo . 'translations', []),
        );

        return new self(
            translations: $translations,
            published: $request->input('published', []),
            twitter_card: $request->input($seo . 'twitter_card', new Missing()),
            og_image: $request->input($seo . 'og_image_id', new Missing()),
            model_id: $request->input($seo . 'model_id', new Missing()),
            model_type: $request->input($seo . 'model_type', new Missing()),
        );
    }

    /**
     * @return array<SeoMetadataTranslationDto>
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * @return array<string>
     */
    public function getPublished(): array
    {
        return $this->published;
    }

    public function getTwitterCard(): Missing|TwitterCardType|null
    {
        return $this->twitter_card;
    }

    public function getOgImage(): Missing|string|null
    {
        return $this->og_image;
    }

    public function getModelId(): Missing|string|null
    {
        return $this->model_id;
    }

    public function getModelType(): Missing|string|null
    {
        return $this->model_type;
    }
}
