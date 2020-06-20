<?php

namespace App\Http\Resources;

class PageResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function base($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'public' => $this->public,
            'content_md' => $this->content_md,
            'content_html' => $this->content_html,
        ];
    }
}
