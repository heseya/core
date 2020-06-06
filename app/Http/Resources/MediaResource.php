<?php

namespace App\Http\Resources;

class MediaResource extends Resource
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
            'type' => 'photo',
            'url' => $this->url,
        ];
    }
}
