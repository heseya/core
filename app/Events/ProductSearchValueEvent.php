<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductSearchValueEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly array $product_ids,
    ) {}
}
