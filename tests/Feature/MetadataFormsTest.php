<?php

namespace Tests\Feature;

use App\Models\Product;
use Domain\Currency\Currency;
use Domain\Metadata\Enums\MetadataType;
use Domain\ProductSet\Events\ProductSetCreated;
use Domain\ProductSet\ProductSet;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MetadataFormsTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testProductCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $prices = array_map(fn (Currency $currency) => [
            'value' => '10.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', [
                'translations' => [
                    $this->lang => ['name' => 'Test'],
                ],
                'published' => [$this->lang],
                'slug' => 'test',
                'prices_base' => $prices,
                'public' => true,
                'shipping_digital' => false,
                'metadata' => [
                    'test' => '123',
                ],
                'metadata_private' => [
                    'test-two' => 123,
                ],
            ]);

        $response->assertCreated();
        $product = Product::query()->find($response->json('data.id'))->first();

        $this->assertDatabaseHas('metadata', [
            'model_id' => $product->getKey(),
            'model_type' => $product->getMorphClass(),
            'name' => 'test',
            'value' => '123',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this->assertDatabaseHas('metadata', [
            'model_id' => $product->getKey(),
            'model_type' => $product->getMorphClass(),
            'name' => 'test-two',
            'value' => 123,
            'value_type' => MetadataType::NUMBER,
            'public' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinimal(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/product-sets', [
                'translations' => [
                    $this->lang => ['name' => 'Test'],
                ],
                'published' => [$this->lang],
                'slug_suffix' => 'test',
                'slug_override' => true,
                'public' => true,
                'metadata' => [
                    'test' => '123',
                ],
                'metadata_private' => [
                    'test-two' => 123,
                ],
            ]);

        $response->assertCreated();
        $set = ProductSet::query()->find($response->json('data.id'))->first();

        $this->assertDatabaseHas('metadata', [
            'model_id' => $set->getKey(),
            'model_type' => $set->getMorphClass(),
            'name' => 'test',
            'value' => '123',
            'value_type' => MetadataType::STRING,
            'public' => 1,
        ]);

        $this->assertDatabaseHas('metadata', [
            'model_id' => $set->getKey(),
            'model_type' => $set->getMorphClass(),
            'name' => 'test-two',
            'value' => 123,
            'value_type' => MetadataType::NUMBER,
            'public' => 0,
        ]);
    }
}
