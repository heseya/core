<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PageResource extends Resource
{
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
            'content_md' => $this->content_md,
            'content_html' => $this->content_html,
        ];
    }
}
