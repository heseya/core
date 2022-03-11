<?php

namespace Tests\Feature;

use App\Enums\MetadataType;
use App\Models\App;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Media;
use App\Models\Metadata;
use App\Models\Option;
use App\Models\Order;
use App\Models\PackageTemplate;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Role;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\User;
use Tests\TestCase;

class MetadataFirstPartTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function dataProvider(): array
    {
        return [
            'products as user' => ['user', ['model' => Product::class, 'prefix_url' => 'products', 'role' => 'products.edit']],
            'products as app' => ['application', ['model' => Product::class, 'prefix_url' => 'products', 'role' => 'products.edit']],

            'schemas as user' => ['user', ['model' => Schema::class, 'prefix_url' => 'schemas', 'role' => 'products.edit']],
            'schemas as application' => ['application', ['model' => Schema::class, 'prefix_url' => 'schemas', 'role' => 'products.edit']],

            'options as user' => ['user', ['model' => Option::class, 'prefix_url' => 'options', 'role' => 'products.edit']],
            'options as application' => ['application', ['model' => Option::class, 'prefix_url' => 'options', 'role' => 'products.edit']],

            'product sets as user' => ['user', ['model' => ProductSet::class, 'prefix_url' => 'product-sets', 'role' => 'product_sets.edit']],
            'product sets as application' => ['application', ['model' => ProductSet::class, 'prefix_url' => 'product-sets', 'role' => 'product_sets.edit']],

            'discounts as user' => ['user', ['model' => Discount::class, 'prefix_url' => 'discounts', 'role' => 'discounts.edit']],
            'discounts as application' => ['application', ['model' => Discount::class, 'prefix_url' => 'discounts', 'role' => 'discounts.edit']],

            'items as user' => ['user', ['model' => Item::class, 'prefix_url' => 'items', 'role' => 'items.edit']],
            'items as application' => ['application', ['model' => Item::class, 'prefix_url' => 'items', 'role' => 'items.edit']],

            'orders as user' => ['user', ['model' => Order::class, 'prefix_url' => 'orders', 'role' => 'orders.edit']],
            'orders as application' => ['application', ['model' => Order::class, 'prefix_url' => 'orders', 'role' => 'orders.edit']],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAddMetadata($user, $data): void
    {
        $this->$user->givePermissionTo($data['role']);

        $related = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => (Schema::factory()->create())->getKey()
            ];
        }

        $object = ($data['model'])::factory()->create($related);

        $metadata = [
            'sample text metadata' => 'Lorem ipsum dolor sit amet',
            'sample numeric metadata' => 21.5,
            'sample bool metadata' => true
        ];

        $this->actingAs($this->$user)->patchJson("/{$data['prefix_url']}/id:{$object->getKey()}/metadata",
            $metadata)
            ->assertOk()
            ->assertJsonFragment(['data' => $metadata]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAddMetadataPrivate($user, $data): void
    {
        $this->$user->givePermissionTo($data['role']);

        $related = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => (Schema::factory()->create())->getKey()
            ];
        }

        $object = ($data['model'])::factory()->create($related);

        $metadata = [
            'sample text metadata private' => 'Aliquam porta viverra tortor non faucibus',
            'sample numeric metadata private' => 22.5,
            'sample bool metadata private' => true
        ];

        $this->actingAs($this->$user)->patchJson("/{$data['prefix_url']}/id:{$object->getKey()}/metadata-private",
            $metadata)
            ->assertOk()
            ->assertJsonFragment(['data' => $metadata]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUpdateMetadata($user, $data): void
    {
        $this->$user->givePermissionTo($data['role']);

        $related = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => (Schema::factory()->create())->getKey()
            ];
        }

        $object = ($data['model'])::factory()->create($related);

        $metadata = $object->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this->actingAs($this->$user)->patchJson("/{$data['prefix_url']}/id:{$object->getKey()}/metadata",
            [
                $metadata->name => 'new super value',
            ])
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata->name => 'new super value',
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMetadataSameKeys($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $product = Product::factory()->create();

        $metadata = $product->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $product2 = Product::factory()->create();

        $metadata2 = $product2->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this->actingAs($this->$user)->patchJson("/products/id:{$product->getKey()}/metadata",
            [
                $metadata->name => 'new super value',
            ])
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata->name => 'new super value',
            ]]);

        $this->assertDatabaseHas('metadata', array_merge($metadata2->toArray(), [
            'model_id' => $product2->getKey(),
            'model_type' => Product::class,
        ]));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUpdateMetadataPrivate($user, $data): void
    {
        $this->$user->givePermissionTo($data['role']);

        $related = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => (Schema::factory()->create())->getKey()
            ];
        }

        $object = ($data['model'])::factory()->create($related);

        $metadata = $object->metadataPrivate()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $this->actingAs($this->$user)->patchJson("/{$data['prefix_url']}/id:{$object->getKey()}/metadata-private",
            [
                $metadata->name => 'new super value',
            ])
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata->name => 'new super value',
            ]]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDeleteMetadata($user, $data): void
    {
        $this->$user->givePermissionTo($data['role']);

        $related = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => (Schema::factory()->create())->getKey()
            ];
        }

        $object = ($data['model'])::factory()->create($related);

        $metadata1 = $object->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $metadata2 = $object->metadata()->create([
            'name' => 'Metadata2',
            'value' => 'metadata2 test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);


        $this->actingAs($this->$user)->patchJson("/{$data['prefix_url']}/id:{$object->getKey()}/metadata",
            [
                $metadata1->name => $metadata1->value,
                $metadata2->name => null,
            ])
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata1->name => $metadata1->value,
            ]])
            ->assertJsonMissing([
                $metadata2->name => $metadata2->value
            ]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDeleteMetadataPrivate($user, $data): void
    {
        $this->$user->givePermissionTo($data['role']);

        $related = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => (Schema::factory()->create())->getKey()
            ];
        }

        $object = ($data['model'])::factory()->create($related);

        $metadata1 = $object->metadataPrivate()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $metadata2 = $object->metadataPrivate()->create([
            'name' => 'Metadata2 private',
            'value' => 'metadata2 test private',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);


        $this->actingAs($this->$user)->patchJson("/{$data['prefix_url']}/id:{$object->getKey()}/metadata-private",
            [
                $metadata1->name => $metadata1->value,
                $metadata2->name => null,
            ])
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata1->name => $metadata1->value,
            ]])
            ->assertJsonMissing([
                $metadata2->name => $metadata2->value
            ]);
    }
}
