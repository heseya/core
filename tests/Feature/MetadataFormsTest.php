<?php

namespace Tests\Feature;

use App\Enums\MetadataType;
use App\Events\ProductSetCreated;
use App\Models\Product;
use Domain\Currency\Currency;
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

        $this->assertDatabaseHas('metadata', [
            'model_id' => $response->json('data.id'),
            'model_type' => Product::class,
            'name' => 'test',
            'value' => '123',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this->assertDatabaseHas('metadata', [
            'model_id' => $response->json('data.id'),
            'model_type' => Product::class,
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

        $this->assertDatabaseHas('metadata', [
            'model_id' => $response->json('data.id'),
            'model_type' => ProductSet::class,
            'name' => 'test',
            'value' => '123',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this->assertDatabaseHas('metadata', [
            'model_id' => $response->json('data.id'),
            'model_type' => ProductSet::class,
            'name' => 'test-two',
            'value' => 123,
            'value_type' => MetadataType::NUMBER,
            'public' => false,
        ]);
    }
}
