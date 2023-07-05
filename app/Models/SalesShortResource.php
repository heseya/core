<?php

namespace App\Models;

use Brick\Money\Money;

class SalesShortResource
{
    public function __construct(
        public string $id,
        public string $name,
        public Money $value,
    ) {
    }
}
