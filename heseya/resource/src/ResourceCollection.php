<?php

namespace Heseya\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResourceCollection extends AnonymousResourceCollection
{
    protected bool $full = false;

    public function full(bool $full = true): self
    {
        $this->full = $full;

        return $this;
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param Request $request
     */
    public function toArray($request): array
    {
        return $this->collection->each(fn ($el) => $el->setIsIndex(!$this->full)->toArray($request))->all();
    }
}
