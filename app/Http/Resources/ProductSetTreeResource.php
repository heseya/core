<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use Illuminate\Http\Request;

class ProductSetTreeResource extends Resource implements ProductSetResourceSwagger
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'public' => $this->public,
            'hide_on_index' => $this->hide_on_index,
            'subsets' => ProductSetTreeResource::collect($this->children),
        ];
    }
}
