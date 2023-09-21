<?php

namespace App\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductSearchValueEvent implements ShouldQueue
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly array $product_ids,
    ) {}
}
