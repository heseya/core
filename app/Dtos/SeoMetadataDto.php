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
    private Missing|string|null $twitter_card;
    private Missing|string|null $og_image;
    private Missing|string|null $model_id;
    private Missing|string|null $model_type;
    private bool|Missing $no_index;
    private array|Missing|null $header_tags;
    /** @var array<string, array<string, string>> */
    public array $translations;
    /** @var array<string> */
    private array $published;

    /**
     * @throws DtoException
     */
    public static function instantiateFromRequest(FormRequest|SeoRequestContract $request): self
    {
        if (!($request instanceof Request)) {
            throw new DtoException('$request must be an instance of Illuminate\Http\Request');
        }

        $seo = $request->has('seo') ? 'seo.' : '';

        $translations = array_map(
            fn ($data) => SeoMetadataTranslationDto::fromDataArray(is_string($data) ? [$data] : $data)->toArray(),
            $request->input($seo . 'translations', []),
        );

        return new self(
            translations: $translations,
            published: $request->input('published', []),
            twitter_card: $request->input($seo . 'twitter_card', new Missing()),
            og_image: $request->input($seo . 'og_image_id', new Missing()),
            model_id: $request->input($seo . 'model_id', new Missing()),
            model_type: $request->input($seo . 'model_type', new Missing()),
            no_index: $request->input($seo . 'no_index', new Missing()),
            header_tags: $request->input($seo . 'header_tags', new Missing()),
        );
    }
}
