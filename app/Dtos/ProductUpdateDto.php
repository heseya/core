<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;

class ProductUpdateDto extends Dto implements InstantiateFromRequest
{
    public string|Missing $name;
    public string|Missing $slug;
    public float|Missing $price;
    public bool|Missing $public;

    public int|Missing $order;
    public float|Missing $quantity_step;
    public int|null|Missing $google_product_category;

    public string|null|Missing $description_html;
    public string|null|Missing $description_short;

    public array|Missing $media;
    public array|Missing $tags;
    public array|Missing $schemas;
    public array|Missing $sets;
    public array|Missing $items;
    public SeoMetadataDto $seo;
    public array|Missing $attributes;

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            slug: $request->input('slug', new Missing()),
            price: $request->input('price', new Missing()),
            public: $request->input('public', new Missing()),
            order: $request->input('order', new Missing()),
            quantity_step: $request->input('quantity_step', new Missing()),
            description_html: $request->input('description_html', new Missing()),
            description_short: $request->input('description_short', new Missing()),
            media: $request->input('media', new Missing()),
            tags: $request->input('tags', new Missing()),
            schemas: $request->input('schemas', new Missing()),
            sets: $request->input('sets', new Missing()),
            items: $request->input('items', new Missing()),
            seo: SeoMetadataDto::instantiateFromRequest($request),
            attributes: $request->input('attributes', new Missing()),
            google_product_category: $request->input('google_product_category', new Missing()),
        );
    }

    public function getMedia(): Missing|array
    {
        return $this->media;
    }

    public function getTags(): Missing|array
    {
        return $this->tags;
    }

    public function getSchemas(): Missing|array
    {
        return $this->schemas;
    }

    public function getSets(): Missing|array
    {
        return $this->sets;
    }

    public function getItems(): Missing|array
    {
        return $this->items;
    }

    public function getSeo(): SeoMetadataDto
    {
        return $this->seo;
    }

    public function getMetadata(): Missing
    {
        return new Missing();
    }

    public function getAttributes(): Missing|array
    {
        return $this->attributes;
    }
}
