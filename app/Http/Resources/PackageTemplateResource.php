<?php

namespace App\Http\Resources;

class PackageTemplateResource extends Resource
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
            'name' => $this->name,
            'weight' => $this->weight,
            'width' => $this->width,
            'height' => $this->height,
            'depth' => $this->depth,
        ];
    }
}
