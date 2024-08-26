<?php

declare(strict_types=1);

namespace Support\LaravelData\Transformers;

use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Transformers\Transformer;

final class MoneyToAmountTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value): BigDecimal
    {
        // @var Money $value
        return $value->getAmount();
    }
}
