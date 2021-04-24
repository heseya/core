<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CategoryResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'slug' => $this->slug,
            'name' => $this->name,
            'public' => $this->public,
            'hide_on_index' => $this->hide_on_index,
        ];
    }
}
