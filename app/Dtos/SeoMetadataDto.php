<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\Contracts\SeoRequestContract;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class SeoMetadataDto extends Dto implements InstantiateFromRequest
{
    private string|null|Missing $title;
    private string|null|Missing $description;
    private array|null|Missing $keywords;
    private string|null|Missing $twitter_card;
    private string|null|Missing $og_image;
    private string|null|Missing $model_id;
    private string|null|Missing $model_type;
    private bool|Missing $no_index;

    /**
     * @throws DtoException
     */
    public static function instantiateFromRequest(FormRequest|SeoRequestContract $request): self
    {
        if (!($request instanceof Request)) {
            throw new DtoException('$request must be an instance of Illuminate\Http\Request');
        }

        $seo = $request->has('seo') ? 'seo.' : '';

        return new self(
            title: $request->input($seo . 'title', new Missing()),
            description: $request->input($seo . 'description', new Missing()),
            keywords: $request->input($seo . 'keywords', new Missing()),
            twitter_card: $request->input($seo . 'twitter_card', new Missing()),
            og_image: $request->input($seo . 'og_image_id', new Missing()),
            model_id: $request->input($seo . 'model_id', new Missing()),
            model_type: $request->input($seo . 'model_type', new Missing()),
            no_index: $request->input($seo . 'no_index', new Missing()),
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

    public function getTwitterCard(): Missing|string|null
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

    public function getNoIndex(): Missing|bool
    {
        return $this->no_index;
    }
}
