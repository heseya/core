<?php

declare(strict_types=1);

namespace Support\LaravelData\Transformers;

use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Transformers\Transformer;

final class WithoutWrappingTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value): DataCollection
    {
        assert($value instanceof DataCollection);

        return $value->withoutWrapping();
    }
}
