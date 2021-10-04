<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductResourceSwagger;
use App\Services\Contracts\MarkdownServiceContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource implements ProductResourceSwagger
{
    protected MarkdownServiceContract $markdownService;

    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->markdownService = app(MarkdownServiceContract::class);
    }

    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'slug' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'public' => $this->public,
            'visible' => $this->isPublic(),
            'available' => $this->available,
            'quantity_step' => $this->quantity_step,
            'brand' => ProductSetResource::make($this->brand),
            'category' => ProductSetResource::make($this->category),
            'cover' => MediaResource::make($this->media()->first()),
            'tags' => TagResource::collection($this->tags),
        ];
    }

    public function view(Request $request): array
    {
        $sets = Auth::check() ? $this->sets : $this->sets()->public()->get();

        return [
            'user_id' => $this->user_id,
            'original_id' => $this->original_id,
            'description_html' => $this->description_html,
            'description_md' => $this->description_html === null ?:
                $this->markdownService->fromHtml($this->description_html),
            'meta_description' => str_replace("\n", ' ', trim(strip_tags($this->description_html))),
            'gallery' => MediaResource::collection($this->media),
            'schemas' => SchemaResource::collection($this->schemas),
            'sets' => ProductSetResource::collection($sets),
        ];
    }
}
