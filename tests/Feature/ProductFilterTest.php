<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Product;
use App\Services\SchemaCrudService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductFilterTest extends TestCase
{
    use RefreshDatabase;

    private SchemaCrudService $schemaCrudService;

    public function setUp(): void
    {
        parent::setUp();

        $this->schemaCrudService = App::make(SchemaCrudService::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testHasItems($user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_hidden']);

        $productWithoutItems = Product::factory()->create();
        $item = Item::factory()->create();

        /** @var Product $productWithItems */
        $productWithItems = Product::factory()->create();
        $productWithItems->items()->attach([$item->getKey() => ['required_quantity' => 1]]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['has_items' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productWithItems->getKey()])
            ->assertJsonMissing(['id' => $productWithoutItems->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testHasSchemas($user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_hidden']);

        $productWithoutSchemas = Product::factory()->create();
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

        /** @var Product $productWithSchemas */
        $productWithSchemas = Product::factory()->create();
        $productWithSchemas->schemas()->attach($schema->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['has_schemas' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productWithSchemas->getKey()])
            ->assertJsonMissing(['id' => $productWithoutSchemas->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShippingDigital($user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_hidden']);

        $productPhysical = Product::factory()->create(['shipping_digital' => false]);
        $productDigital = Product::factory()->create(['shipping_digital' => true]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['shipping_digital' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productDigital->getKey()])
            ->assertJsonMissing(['id' => $productPhysical->getKey()]);
    }
}
