<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexPerformanceSchema500(): void
    {
        $this->user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $schema1 = Schema::factory()->create([
            'type' => 'select',
            'hidden' => false,
        ]);
        $schema2 = Schema::factory()->create([
            'type' => 'select',
            'hidden' => false,
        ]);
        $schema3 = Schema::factory()->create([
            'type' => 'select',
            'hidden' => false,
        ]);

        $product->schemas()->save($schema1);
        $product->schemas()->save($schema2);
        $product->schemas()->save($schema3);

        Option::factory()->count(500)->create([
            'schema_id' => $schema1->getKey(),
        ]);
        Option::factory()->count(500)->create([
            'schema_id' => $schema2->getKey(),
        ]);
        Option::factory()->count(500)->create([
            'schema_id' => $schema3->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', '/products/id:' . $product->getKey())
            ->assertOk();

        $this->assertQueryCountLessThan(30);
    }
}
