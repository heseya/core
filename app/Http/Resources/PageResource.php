<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\PageResourceSwagger;
use App\Services\Contracts\MarkdownServiceContract;
use Illuminate\Http\Request;

class PageResource extends Resource implements PageResourceSwagger
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
            'public' => $this->public,
        ];
    }

    public function view(Request $request): array
    {
        return [
            'content_html' => $this->content_html,
            'content_md' => $this->markdownService->fromHtml($this->content_html),
            'meta_description' => str_replace("\n", ' ', trim(strip_tags($this->content_html))),
        ];
    }
}
