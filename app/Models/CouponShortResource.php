<?php

namespace App\Models;

use Brick\Money\Money;

class CouponShortResource extends SalesShortResource
{
    public function __construct(
        public string $id,
        public string $name,
        public Money $value,
        public string $code,
    ) {
        parent::__construct($this->id, $this->name, $this->value);
    }
}
