<?php

namespace Tests\Feature;

use App\Enums\MetadataType;
use App\Models\App;
use App\Models\Banner;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Media;
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

class MetadataTest extends TestCase
{
    public static function dataProvider(): array
    {
        return [
            'products as user' => [
                'user',
                ['model' => Product::class, 'prefix_url' => 'products', 'role' => 'products.edit'],
            ],
            'products as app' => [
                'application',
                ['model' => Product::class, 'prefix_url' => 'products', 'role' => 'products.edit'],
            ],

            'schemas as user' => [
                'user',
                ['model' => Schema::class, 'prefix_url' => 'schemas', 'role' => 'products.edit'],
            ],
            'schemas as application' => [
                'application',
                ['model' => Schema::class, 'prefix_url' => 'schemas', 'role' => 'products.edit'],
            ],

            'options as user' => [
                'user',
                ['model' => Option::class, 'prefix_url' => 'options', 'role' => 'products.edit'],
            ],
            'options as application' => [
                'application',
                ['model' => Option::class, 'prefix_url' => 'options', 'role' => 'products.edit'],
            ],

            'product sets as user' => [
                'user',
                ['model' => ProductSet::class, 'prefix_url' => 'product-sets', 'role' => 'product_sets.edit'],
            ],
            'product sets as application' => [
                'application',
                ['model' => ProductSet::class, 'prefix_url' => 'product-sets', 'role' => 'product_sets.edit'],
            ],

            'coupons as user' => [
                'user',
                ['model' => Discount::class, 'prefix_url' => 'coupons', 'role' => 'coupons.edit'],
            ],
            'coupons as application' => [
                'application',
                ['model' => Discount::class, 'prefix_url' => 'coupons', 'role' => 'coupons.edit'],
            ],

            'sales as user' => [
                'user',
                ['model' => Discount::class, 'prefix_url' => 'sales', 'role' => 'sales.edit'],
            ],
            'sales as application' => [
                'application',
                ['model' => Discount::class, 'prefix_url' => 'sales', 'role' => 'sales.edit'],
            ],

            'items as user' => [
                'user',
                ['model' => Item::class, 'prefix_url' => 'items', 'role' => 'items.edit'],
            ],
            'items as application' => [
                'application',
                ['model' => Item::class, 'prefix_url' => 'items', 'role' => 'items.edit'],
            ],

            'orders as user' => [
                'user',
                ['model' => Order::class, 'prefix_url' => 'orders', 'role' => 'orders.edit'],
            ],
            'orders as application' => [
                'application',
                ['model' => Order::class, 'prefix_url' => 'orders', 'role' => 'orders.edit'],
            ],

            'statuses as user' => [
                'user',
                ['model' => Status::class, 'prefix_url' => 'statuses', 'role' => 'statuses.edit'],
            ],
            'statuses as application' => [
                'application',
                ['model' => Status::class, 'prefix_url' => 'statuses', 'role' => 'statuses.edit'],
            ],

            'shipping methods as user' => [
                'user',
                [
                    'model' => ShippingMethod::class,
                    'prefix_url' => 'shipping-methods',
                    'role' => 'shipping_methods.edit',
                ],
            ],
            'shipping methods as application' => [
                'application',
                ['model' => ShippingMethod::class,
                    'prefix_url' => 'shipping-methods',
                    'role' => 'shipping_methods.edit',
                ],
            ],

            'package templates as user' => [
                'user', [
                    'model' => PackageTemplate::class,
                    'prefix_url' => 'package-templates',
                    'role' => 'packages.edit',
                ],
            ],
            'package templates as application' => [
                'application', [
                    'model' => PackageTemplate::class,
                    'prefix_url' => 'package-templates',
                    'role' => 'packages.edit',
                ],
            ],

            'users as user' => [
                'user',
                ['model' => User::class, 'prefix_url' => 'users', 'role' => 'users.edit'],
            ],
            'users as application' => [
                'application',
                ['model' => User::class, 'prefix_url' => 'users', 'role' => 'users.edit'],
            ],

            'roles as user' => [
                'user',
                ['model' => Role::class, 'prefix_url' => 'roles', 'role' => 'roles.edit'],
            ],
            'roles as application' => [
                'application',
                ['model' => Role::class, 'prefix_url' => 'roles', 'role' => 'roles.edit'],
            ],

            'pages as user' => [
                'user',
                ['model' => Page::class, 'prefix_url' => 'pages', 'role' => 'pages.edit'],
            ],
            'pages as application' => [
                'application',
                ['model' => Page::class, 'prefix_url' => 'pages', 'role' => 'pages.edit'],
            ],

            'apps as user' => [
                'user',
                ['model' => App::class, 'prefix_url' => 'apps', 'role' => 'apps.install'],
            ],
            'apps as application' => [
                'application',
                ['model' => App::class, 'prefix_url' => 'apps', 'role' => 'apps.install'],
            ],

            'media as user' => [
                'user',
                ['model' => Media::class, 'prefix_url' => 'media', 'role' => 'media.edit'],
            ],
            'media as application' => [
                'application',
                ['model' => Media::class, 'prefix_url' => 'media', 'role' => 'media.edit'],
            ],

            'banners as user' => [
                'user',
                ['model' => Banner::class, 'prefix_url' => 'banners', 'role' => 'banners.edit'],
            ],
            'banners as application' => [
                'application',
                ['model' => Banner::class, 'prefix_url' => 'banners', 'role' => 'banners.edit'],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAddMetadata($user, $data): void
    {
        $this->{$user}->givePermissionTo($data['role']);

        $related = [];
        $code = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => Schema::factory()->create()->getKey(),
            ];
        }

        if ($data['prefix_url'] === 'sales') {
            $code = ['code' => null];
        }

        $object = $data['model']::factory()->create($related + $code);

        $metadata = [
            'sample text metadata' => 'Lorem ipsum dolor sit amet',
            'sample numeric metadata' => 21.5,
            'sample bool metadata' => true,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            "/{$data['prefix_url']}/id:{$object->getKey()}/metadata",
            $metadata
        );

        $response
            ->assertOk()
            ->assertJsonFragment(['data' => $metadata]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAddMetadataPrivate($user, $data): void
    {
        $this->{$user}->givePermissionTo($data['role']);

        $related = [];
        $code = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => Schema::factory()->create()->getKey(),
            ];
        }

        if ($data['prefix_url'] === 'sales') {
            $code = ['code' => null];
        }

        $object = $data['model']::factory()->create($related + $code);

        $metadata = [
            'sample text metadata private' => 'Aliquam porta viverra tortor non faucibus',
            'sample numeric metadata private' => 22.5,
            'sample bool metadata private' => true,
        ];

        $this->actingAs($this->{$user})->patchJson(
            "/{$data['prefix_url']}/id:{$object->getKey()}/metadata-private",
            $metadata
        )
            ->assertOk()
            ->assertJsonFragment(['data' => $metadata]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUpdateMetadata($user, $data): void
    {
        $this->{$user}->givePermissionTo($data['role']);

        $related = [];
        $code = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => Schema::factory()->create()->getKey(),
            ];
        }

        if ($data['prefix_url'] === 'sales') {
            $code = ['code' => null];
        }

        $object = $data['model']::factory()->create($related + $code);

        $metadata = $object->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this->actingAs($this->{$user})->patchJson(
            "/{$data['prefix_url']}/id:{$object->getKey()}/metadata",
            [
                $metadata->name => 'new super value',
            ]
        )
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata->name => 'new super value',
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMetadataSameKeys($user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

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

        $this->actingAs($this->{$user})->patchJson(
            "/products/id:{$product->getKey()}/metadata",
            [
                $metadata->name => 'new super value',
            ]
        )
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata->name => 'new super value',
            ],
            ]);

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
        $this->{$user}->givePermissionTo($data['role']);

        $related = [];
        $code = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => Schema::factory()->create()->getKey(),
            ];
        }

        if ($data['prefix_url'] === 'sales') {
            $code = ['code' => null];
        }

        $object = $data['model']::factory()->create($related + $code);

        $metadata = $object->metadataPrivate()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $this->actingAs($this->{$user})->patchJson(
            "/{$data['prefix_url']}/id:{$object->getKey()}/metadata-private",
            [
                $metadata->name => 'new super value',
            ]
        )
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata->name => 'new super value',
            ],
            ]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDeleteMetadata($user, $data): void
    {
        $this->{$user}->givePermissionTo($data['role']);

        $related = [];
        $code = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => Schema::factory()->create()->getKey(),
            ];
        }

        if ($data['prefix_url'] === 'sales') {
            $code = ['code' => null];
        }

        $object = $data['model']::factory()->create($related + $code);

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

        $this->actingAs($this->{$user})->patchJson(
            "/{$data['prefix_url']}/id:{$object->getKey()}/metadata",
            [
                $metadata1->name => $metadata1->value,
                $metadata2->name => null,
            ]
        )
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata1->name => $metadata1->value,
            ],
            ])
            ->assertJsonMissing([
                $metadata2->name => $metadata2->value,
            ]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDeleteMetadataPrivate($user, $data): void
    {
        $this->{$user}->givePermissionTo($data['role']);

        $related = [];
        $code = [];

        if ($data['model'] === Option::class) {
            $related = [
                'schema_id' => Schema::factory()->create()->getKey(),
            ];
        }

        if ($data['prefix_url'] === 'sales') {
            $code = ['code' => null];
        }

        $object = $data['model']::factory()->create($related + $code);

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

        $this->actingAs($this->{$user})->patchJson(
            "/{$data['prefix_url']}/id:{$object->getKey()}/metadata-private",
            [
                $metadata1->name => $metadata1->value,
                $metadata2->name => null,
            ]
        )
            ->assertOk()
            ->assertJsonFragment(['data' => [
                $metadata1->name => $metadata1->value,
            ],
            ])
            ->assertJsonMissing([
                $metadata2->name => $metadata2->value,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexEmptyObject($user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $response = $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                "/products/id:{$product->getKey()}",
            )
            ->assertOk();

        $this->assertStringContainsString('"metadata":{}', $response->getContent());
    }

    public function testAddMyMetadataPersonal(): void
    {
        $metadata = [
            'sample text metadata' => 'Lorem ipsum dolor sit amet',
            'sample numeric metadata' => 21.5,
            'sample bool metadata' => true,
        ];

        $this->actingAs($this->user)->json(
            'PATCH',
            '/auth/profile/metadata-personal',
            $metadata
        )
            ->assertOk()
            ->assertJsonFragment(['data' => $metadata]);

        $this->assertDatabaseHas('metadata_personals', [
            'model_type' => $this->user::class,
            'model_id' => $this->user->getKey(),
            'name' => 'sample text metadata',
            'value' => 'Lorem ipsum dolor sit amet',
        ]);
        $this->assertDatabaseHas('metadata_personals', [
            'model_type' => $this->user::class,
            'model_id' => $this->user->getKey(),
            'name' => 'sample numeric metadata',
            'value' => 21.5,
        ]);
        $this->assertDatabaseHas('metadata_personals', [
            'model_type' => $this->user::class,
            'model_id' => $this->user->getKey(),
            'name' => 'sample bool metadata',
            'value' => true,
        ]);
    }

    public function testUpdateMyMetadataPersonal(): void
    {
        $this->user->metadataPersonal()->create([
            'name' => 'sample text metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
        ]);

        $metadata = [
            'sample text metadata' => 'Lorem ipsum dolor sit amet',
        ];

        $this->actingAs($this->user)->json(
            'PATCH',
            '/auth/profile/metadata-personal',
            $metadata
        )
            ->assertOk()
            ->assertJsonFragment(['data' => $metadata]);

        $this->assertDatabaseHas('metadata_personals', [
            'model_type' => $this->user::class,
            'model_id' => $this->user->getKey(),
            'name' => 'sample text metadata',
            'value' => 'Lorem ipsum dolor sit amet',
        ]);
        $this->assertDatabaseMissing('metadata_personals', [
            'model_type' => $this->user::class,
            'model_id' => $this->user->getKey(),
            'name' => 'sample text metadata',
            'value' => 'metadata test',
        ]);
    }

    public function testDeleteMyMetadataPersonal(): void
    {
        $this->user->metadataPersonal()->create([
            'name' => 'sample text metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
        ]);

        $metadata = [
            'sample text metadata' => null,
        ];

        $this->actingAs($this->user)->json(
            'PATCH',
            '/auth/profile/metadata-personal',
            $metadata
        )
            ->assertOk()
            ->assertJsonFragment(['data' => []]);

        $this->assertDatabaseMissing('metadata_personals', [
            'model_type' => $this->user::class,
            'model_id' => $this->user->getKey(),
            'name' => 'sample text metadata',
            'value' => 'metadata test',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddUserMetadataPersonal($user): void
    {
        $this->{$user}->givePermissionTo('users.edit');
        $model = User::factory()->create();
        $metadata = [
            'sample text metadata' => 'Lorem ipsum dolor sit amet',
            'sample numeric metadata' => 21.5,
            'sample bool metadata' => true,
        ];

        $this
            ->actingAs($this->{$user})
            ->json(
                'PATCH',
                "/users/id:{$model->getKey()}/metadata-personal",
                $metadata
            )
            ->assertOk()
            ->assertJsonFragment(['data' => $metadata]);

        $this->assertDatabaseHas('metadata_personals', [
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'name' => 'sample text metadata',
            'value' => 'Lorem ipsum dolor sit amet',
        ]);
        $this->assertDatabaseHas('metadata_personals', [
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'name' => 'sample numeric metadata',
            'value' => 21.5,
        ]);
        $this->assertDatabaseHas('metadata_personals', [
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'name' => 'sample bool metadata',
            'value' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUserMetadataPersonal($user): void
    {
        $this->{$user}->givePermissionTo('users.edit');
        $model = User::factory()->create();

        $this->user->metadataPersonal()->create([
            'name' => 'sample text metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
        ]);

        $metadata = [
            'sample text metadata' => 'Lorem ipsum dolor sit amet',
        ];

        $this->actingAs($this->{$user})->json(
            'PATCH',
            "/users/id:{$model->getKey()}/metadata-personal",
            $metadata
        )
            ->assertOk()
            ->assertJsonFragment(['data' => $metadata]);

        $this->assertDatabaseHas('metadata_personals', [
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'name' => 'sample text metadata',
            'value' => 'Lorem ipsum dolor sit amet',
        ]);
        $this->assertDatabaseMissing('metadata_personals', [
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'name' => 'sample text metadata',
            'value' => 'metadata test',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteUserMetadataPersonal($user): void
    {
        $this->{$user}->givePermissionTo('users.edit');
        $model = User::factory()->create();

        $model->metadataPersonal()->create([
            'name' => 'sample text metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
        ]);

        $metadata = [
            'sample text metadata' => null,
        ];

        $this->actingAs($this->{$user})->json(
            'PATCH',
            "/users/id:{$model->getKey()}/metadata-personal",
            $metadata
        )
            ->assertOk()
            ->assertJsonFragment(['data' => []]);

        $this->assertDatabaseMissing('metadata_personals', [
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'name' => 'sample text metadata',
            'value' => 'metadata test',
        ]);
    }
}
