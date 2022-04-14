<?php

namespace App\Models;

class SalesShortResource
{
    public function __construct(
        public string $id,
        public string $name,
        public float $value,
    ) {
    }
}
