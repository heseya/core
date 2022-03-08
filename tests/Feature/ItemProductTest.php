<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Product;
use Tests\TestCase;

class ItemProductTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testOne()
    {
        $items = Item::factory()->count(5)->create();
        $product = Product::factory()->create();

        $product->items()->sync($items);
        var_dump($product->items->toArray());
    }
}
