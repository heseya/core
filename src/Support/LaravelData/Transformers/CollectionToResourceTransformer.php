<?php

declare(strict_types=1);

namespace Support\LaravelData\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Transformers\Transformer;

final class CollectionToResourceTransformer implements Transformer
{
    public function __construct(
        protected string $resource,
    ) {
        if (!is_a($resource, JsonResource::class)) {
            throw new InvalidArgumentException();
        }
    }

    public function transform(DataProperty $property, mixed $value): string
    {
        return $this->resource::collection();
    }
}
