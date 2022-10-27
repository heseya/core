<?php

namespace Tests\Feature;

use App\Enums\MetadataType;
use App\Events\ProductSetCreated;
use App\Models\Product;
use App\Models\ProductSet;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MetadataFormsTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testProductCreate($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/products', [
                'name' => 'Test',
                'slug' => 'test',
                'price' => 100.00,
                'public' => true,
                'is_digital' => false,
                'metadata' => [
                    'test' => '123',
                ],
                'metadata_private' => [
                    'test-two' => 123,
                ],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('metadata', [
            'model_id' => $response->getData()->data->id,
            'model_type' => Product::class,
            'name' => 'test',
            'value' => '123',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this->assertDatabaseHas('metadata', [
            'model_id' => $response->getData()->data->id,
            'model_type' => Product::class,
            'name' => 'test-two',
            'value' => 123,
            'value_type' => MetadataType::NUMBER,
            'public' => false,
        ]);
    }

//    /**
//     * @dataProvider authProvider
//     */
//    public function testProductUpdate($user): void
//    {
//        $this->$user->givePermissionTo('products.edit');
//
//        $product = Product::factory()->create();
//
//        $this
//            ->actingAs($this->$user)
//            ->json('PATCH', '/products/id:' . $product->getKey(), [
//                'metadata' => [
//                    'test' => '123',
//                ],
//            ])
//            ->assertUnprocessable();
//    }
//
//    /**
//     * @dataProvider authProvider
//     */
//    public function testProductPrivateUpdate($user): void
//    {
//        $this->$user->givePermissionTo('products.edit');
//
//        $product = Product::factory()->create();
//
//        $this
//            ->actingAs($this->$user)
//            ->json('PATCH', '/products/id:' . $product->getKey(), [
//                'metadata_private' => [
//                    'test' => '123',
//                ],
//            ])
//            ->assertUnprocessable();
//    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinimal($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        Event::fake([ProductSetCreated::class]);

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/product-sets', [
                'name' => 'Test',
                'slug_suffix' => 'test',
                'slug_override' => true,
                'metadata' => [
                    'test' => '123',
                ],
                'metadata_private' => [
                    'test-two' => 123,
                ],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('metadata', [
            'model_id' => $response->getData()->data->id,
            'model_type' => ProductSet::class,
            'name' => 'test',
            'value' => '123',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this->assertDatabaseHas('metadata', [
            'model_id' => $response->getData()->data->id,
            'model_type' => ProductSet::class,
            'name' => 'test-two',
            'value' => 123,
            'value_type' => MetadataType::NUMBER,
            'public' => false,
        ]);
    }
}
