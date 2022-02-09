<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

class PageResource extends Resource
{
    use GetAllTranslations;

    public function base(Request $request): array
    {
        $data = [
            'id' => $this->getKey(),
            'slug' => $this->slug,
            'name' => $this->name,
            'public' => $this->public,
            'order' => $this->order,
        ];

        return array_merge($data, array_key_exists('translations', $request->toArray()) ? $this->getAllTranslations() : []);
    }

    public function view(Request $request): array
    {
        return [
            'content_html' => $this->content_html,
            'meta_description' => str_replace("\n", ' ', trim(strip_tags($this->content_html))),
            'seo' => SeoMetadataResource::make($this->seo),
        ];
    }
}
