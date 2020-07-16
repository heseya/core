<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CategoryResource extends Resource
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
            'slug' => $this->slug,
            'name' => $this->name,
            'public' => $this->public,
        ];
    }
}
