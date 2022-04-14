<?php

namespace App\Models;

class CouponShortResource extends SalesShortResource
{
    public function __construct(
        public string $id,
        public string $name,
        public float $value,
        public string $code,
    ) {
        parent::__construct($this->id, $this->name, $this->value);
    }
}
