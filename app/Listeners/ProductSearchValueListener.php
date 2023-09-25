<?php

namespace App\Listeners;

use App\Events\ProductSearchValueEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class ProductSearchValueListener implements ShouldQueue
{
    public function handle(ProductSearchValueEvent $event): void
    {
        // TODO: remove this listener
    }
}
