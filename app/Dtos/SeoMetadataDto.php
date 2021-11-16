<?php

namespace App\Dtos;

use App\Enums\TwitterCardType;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Requests\SeoMetadataRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class SeoMetadataDto extends Dto
{
    private string|null|Missing $title;
    private string|null|Missing $description;
    private array|null|Missing $keywords;
    private string|null|Missing $twitter_card;
    private string|null|Missing $og_image;
    private string|null|Missing $model_id;
    private string|null|Missing $model_type;

    public static function fromFormRequest(ProductCreateRequest|ProductUpdateRequest|SeoMetadataRequest $request): self
    {
        $seo = $request->has('seo') ? 'seo.' : '';
        return new self(
            title: $request->input($seo . 'title', new Missing()),
            description: $request->input($seo . 'description', new Missing()),
            keywords: $request->input($seo . 'keywords', new Missing()),
            twitter_card: $request->input($seo . 'twitter_card', new Missing()),
            og_image: $request->input($seo . 'og_image_id', new Missing()),
            model_id: $request->input($seo . 'model_id', new Missing()),
            model_type: $request->input($seo . 'model_type', new Missing()),
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
