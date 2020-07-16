<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MediaResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'photo',
            'url' => $this->url,
        ];
    }
}
