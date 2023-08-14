<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

class ConsentResource extends Resource
{
    use GetAllTranslations;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'description_html' => $this->resource->description_html,
            'required' => $this->resource->required,
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ? $this->getAllTranslations() : [],
        ];
    }
}
