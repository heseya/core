<?php

declare(strict_types=1);

namespace Domain\Tag\Resources;

use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use Illuminate\Http\Request;

final class TagResource extends Resource
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
            'color' => $this->resource->color,
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ?
                $this->getAllTranslations('tags.show_hidden') : [],
        ];
    }
}
