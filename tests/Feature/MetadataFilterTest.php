<?php

namespace Tests\Feature;

use App\Enums\MetadataType;
use App\Models\App;
use App\Models\Banner;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Order;
use App\Models\PackageTemplate;
use App\Models\Page;
use App\Models\ProductSet;
use App\Models\Role;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\User;
use Tests\TestCase;

class MetadataFilterTest extends TestCase
{
    public static function dataProvider(): array
    {
        return [
            'schemas as user' => [
                'user',
                [
                    'model' => Schema::class,
                    'prefix_url' => 'schemas',
                    'public_role' => 'products.add',
                    'private_role' => 'schemas.show_metadata_private',
                ],
            ],
            'schemas as application' => [
                'application',
                ['model' => Schema::class,
                    'prefix_url' => 'schemas',
                    'public_role' => 'products.add',
                    'private_role' => 'schemas.show_metadata_private',
                ],
            ],

            'product sets as user' => [
                'user',
                [
                    'model' => ProductSet::class,
                    'prefix_url' => 'product-sets',
                    'public_role' => 'product_sets.show',
                    'private_role' => 'product_sets.show_metadata_private',
                ],
            ],
            'product sets as application' => [
                'application',
                [
                    'model' => ProductSet::class,
                    'prefix_url' => 'product-sets',
                    'public_role' => 'product_sets.show',
                    'private_role' => 'product_sets.show_metadata_private',
                ],
            ],

            'coupons as user' => [
                'user', [
                    'model' => Discount::class,
                    'prefix_url' => 'coupons',
                    'public_role' => 'coupons.show',
                    'private_role' => 'coupons.show_metadata_private',
                ],
            ],
            'coupons as application' => [
                'application',
                [
                    'model' => Discount::class,
                    'prefix_url' => 'coupons',
                    'public_role' => 'coupons.show',
                    'private_role' => 'coupons.show_metadata_private',
                ],
            ],

            'sales as user' => [
                'user', [
                    'model' => Discount::class,
                    'prefix_url' => 'sales',
                    'public_role' => 'sales.show',
                    'private_role' => 'sales.show_metadata_private',
                ],
            ],
            'sales as application' => [
                'application',
                [
                    'model' => Discount::class,
                    'prefix_url' => 'sales',
                    'public_role' => 'sales.show',
                    'private_role' => 'sales.show_metadata_private',
                ],
            ],

            'items as user' => [
                'user',
                [
                    'model' => Item::class,
                    'prefix_url' => 'items',
                    'public_role' => 'items.show',
                    'private_role' => 'items.show_metadata_private',
                ],
            ],
            'items as application' => [
                'application', [
                    'model' => Item::class,
                    'prefix_url' => 'items',
                    'public_role' => 'items.show',
                    'private_role' => 'items.show_metadata_private',
                ],
            ],

            'orders as user' => [
                'user', [
                    'model' => Order::class,
                    'prefix_url' => 'orders',
                    'public_role' => 'orders.show',
                    'private_role' => 'orders.show_metadata_private',
                ],
            ],
            'orders as application' => [
                'application', [
                    'model' => Order::class,
                    'prefix_url' => 'orders',
                    'public_role' => 'orders.show',
                    'private_role' => 'orders.show_metadata_private',
                ],
            ],

            'statuses as user' => [
                'user', [
                    'model' => Status::class,
                    'prefix_url' => 'statuses',
                    'public_role' => 'statuses.show',
                    'private_role' => 'statuses.show_metadata_private',
                ],
            ],
            'statuses as application' => [
                'application',
                [
                    'model' => Status::class,
                    'prefix_url' => 'statuses',
                    'public_role' => 'statuses.show',
                    'private_role' => 'statuses.show_metadata_private',
                ],
            ],

            'shipping methods as user' => [
                'user',
                [
                    'model' => ShippingMethod::class,
                    'prefix_url' => 'shipping-methods',
                    'public_role' => 'shipping_methods.show',
                    'private_role' => 'shipping_methods.show_metadata_private',
                ],
            ],
            'shipping methods as application' => [
                'application',
                [
                    'model' => ShippingMethod::class,
                    'prefix_url' => 'shipping-methods',
                    'public_role' => 'shipping_methods.show',
                    'private_role' => 'shipping_methods.show_metadata_private',
                ],
            ],

            'package templates as user' => [
                'user', [
                    'model' => PackageTemplate::class,
                    'prefix_url' => 'package-templates',
                    'public_role' => 'packages.show',
                    'private_role' => 'packages.show_metadata_private',
                ],
            ],
            'package templates as application' => [
                'application',
                [
                    'model' => PackageTemplate::class,
                    'prefix_url' => 'package-templates',
                    'public_role' => 'packages.show',
                    'private_role' => 'packages.show_metadata_private',
                ],
            ],

            'users as user' => [
                'user',
                [
                    'model' => User::class,
                    'prefix_url' => 'users',
                    'public_role' => 'users.show',
                    'private_role' => 'users.show_metadata_private',
                ],
            ],
            'users as application' => [
                'application',
                [
                    'model' => User::class,
                    'prefix_url' => 'users',
                    'public_role' => 'users.show',
                    'private_role' => 'users.show_metadata_private',
                ],
            ],

            'roles as user' => [
                'user',
                [
                    'model' => Role::class,
                    'prefix_url' => 'roles',
                    'public_role' => 'roles.show',
                    'private_role' => 'roles.show_metadata_private',
                ],
            ],
            'roles as application' => [
                'application',
                [
                    'model' => Role::class,
                    'prefix_url' => 'roles',
                    'public_role' => 'roles.show',
                    'private_role' => 'roles.show_metadata_private',
                ],
            ],

            'pages as user' => [
                'user',
                [
                    'model' => Page::class,
                    'prefix_url' => 'pages',
                    'public_role' => 'pages.show',
                    'private_role' => 'pages.show_metadata_private',
                ],
            ],
            'pages as application' => [
                'application',
                [
                    'model' => Page::class,
                    'prefix_url' => 'pages',
                    'public_role' => 'pages.show',
                    'private_role' => 'pages.show_metadata_private',
                ],
            ],

            'apps as user' => [
                'user',
                [
                    'model' => App::class,
                    'prefix_url' => 'apps',
                    'public_role' => 'apps.show',
                    'private_role' => 'apps.show_metadata_private',
                ],
            ],
            'apps as application' => [
                'application',
                [
                    'model' => App::class,
                    'prefix_url' => 'apps',
                    'public_role' => 'apps.show',
                    'private_role' => 'apps.show_metadata_private',
                ],
            ],

            'banners as user' => [
                'user',
                [
                    'model' => Banner::class,
                    'prefix_url' => 'banners',
                    'public_role' => 'banners.show',
                    'private_role' => 'banners.show_metadata_private',
                ],
            ],
            'banners as application' => [
                'application',
                [
                    'model' => Banner::class,
                    'prefix_url' => 'banners',
                    'public_role' => 'banners.show',
                    'private_role' => 'banners.show_metadata_private',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testQuery($user, $data): void
    {
        $this->{$user}->givePermissionTo($data['public_role']);

        $object = $this->createObjects($data['model'], $data['prefix_url'] === 'sales');

        $metadata = $object->first()->metadata()->create([
            'name' => 'Producent',
            'value' => 'Heseya',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/{$data['prefix_url']}?metadata[{$metadata->name}]={$metadata->value}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                $metadata->name => $metadata->value,
            ])
            ->assertJsonMissing([
                'metadata' => [],
            ]);
    }

    public function createObjects($model, $sale = false)
    {
        $code = [];
        $status = [];

        if ($sale) {
            $code = ['code' => null];
        }

        if ($model === Order::class) {
            $status = Status::factory()->create();
            $status->metadata()->create([
                'name' => 'Status metadata',
                'value' => 'status',
                'value_type' => MetadataType::STRING,
                'public' => true,
            ]);
            $shippingMethod = ShippingMethod::factory()->create();
            $shippingMethod->metadata()->create([
                'name' => 'Shipping Method metadata',
                'value' => 'Value',
                'value_type' => MetadataType::STRING,
                'public' => true,
            ]);
            $status = [
                'status_id' => $status->getKey(),
                'shipping_method_id' => $shippingMethod->getKey(),
            ];
        }

        $objects = $model::factory()->count(3)->create($code + $status);

        if ($objects->first()->public !== null) {
            $objects->each(fn ($object) => $object->update(['public' => true]));
        }

        return $objects;
    }

    /**
     * @dataProvider authProvider
     */
    public function testQueryByMorePropertiesUsingDots($user): void
    {
        $this->{$user}->givePermissionTo('orders.show');

        $objects = $this->createObjects(Order::class);

        $metadata = $objects->first()->metadata()->create([
            'name' => 'Producent',
            'value' => 'Heseya',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $metadata2 = $objects->first()->metadata()->create([
            'name' => 'Kolor',
            'value' => 'Czerwony',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $objects->last()->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $name = $metadata->name;
        $value = $metadata->value;

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                "/orders?metadata.{$name}={$value}&metadata.{$metadata2->name}={$metadata2->value}",
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                $metadata->name => $metadata->value,
            ])
            ->assertJsonFragment([
                $metadata2->name => $metadata2->value,
            ])
            ->assertJsonMissing([
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testQueryCase1($user): void
    {
        $this->{$user}->givePermissionTo('orders.show');

        $objects = $this->createObjects(Order::class);

        $metadata = $objects->first()->metadata()->create([
            'name' => 'test1',
            'value' => 0,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $metadata2 = $objects->first()->metadata()->create([
            'name' => 'test2',
            'value' => 0,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $objects->last()->metadata()->create([
            'name' => 'test2',
            'value' => 1,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/orders?metadata.test1=0&metadata.test2=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                $metadata->name => $metadata->value,
            ])
            ->assertJsonFragment([
                $metadata2->name => $metadata2->value,
            ])
            ->assertJsonMissing([
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testQueryCase2($user): void
    {
        $this->{$user}->givePermissionTo('orders.show');

        $objects = $this->createObjects(Order::class);

        $metadata = $objects->first()->metadata()->create([
            'name' => 'test1',
            'value' => 0,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $objects->last()->metadata()->create([
            'name' => 'test1',
            'value' => 1,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/orders?metadata.test1=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                $metadata->name => $metadata->value,
            ])
            ->assertJsonMissing([
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testQueryCase3($user): void
    {
        $this->{$user}->givePermissionTo('orders.show');

        $objects = $this->createObjects(Order::class);

        $metadata = $objects->first()->metadata()->create([
            'name' => 'test1',
            'value' => 0,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $metadata2 = $objects->first()->metadata()->create([
            'name' => 'test2',
            'value' => 1,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $metadata3 = $objects->last()->metadata()->create([
            'name' => 'test1',
            'value' => 1,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $metadata4 = $objects->last()->metadata()->create([
            'name' => 'test2',
            'value' => 0,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/orders?metadata.test1=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                $metadata->name => $metadata->value,
            ])
            ->assertJsonFragment([
                $metadata2->name => $metadata2->value,
            ])
            ->assertJsonMissing([
                'metadata' => [],
            ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/orders?metadata.test1=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                $metadata3->name => $metadata3->value,
            ])
            ->assertJsonFragment([
                $metadata4->name => $metadata4->value,
            ])
            ->assertJsonMissing([
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testQueryWithoutValue($user): void
    {
        $this->{$user}->givePermissionTo('orders.show');

        $objects = $this->createObjects(Order::class);

        $objects->first()->metadata()->create([
            'name' => 'test1',
            'value' => 123,
            'value_type' => MetadataType::NUMBER,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/orders?metadata[test1]')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testQueryPrivateWithoutValue($user): void
    {
        $this->{$user}->givePermissionTo(['orders.show', 'orders.show_metadata_private']);

        $objects = $this->createObjects(Order::class);

        $objects->first()->metadata()->create([
            'name' => 'test1',
            'value' => 123,
            'value_type' => MetadataType::NUMBER,
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/orders?metadata_private[test1]')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testQueryPrivate2($user, $data): void
    {
        $this->{$user}->givePermissionTo([$data['public_role'], $data['private_role']]);

        $object = $this->createObjects($data['model'], $data['prefix_url'] === 'sales');

        $metadata = $object->first()->metadataPrivate()->create([
            'name' => 'Producent',
            'value' => 'Heseya - private',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/{$data['prefix_url']}?metadata_private[{$metadata->name}]={$metadata->value}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                $metadata->name => $metadata->value,
            ])
            ->assertJsonMissing([
                'metadata_private' => [],
            ]);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testQueryPrivateWithoutPermission($user, $data): void
    {
        $this->{$user}->givePermissionTo($data['public_role']);

        $object = $this->createObjects($data['model']);

        $metadata = $object->first()->metadataPrivate()->create([
            'name' => 'Producent',
            'value' => 'Heseya - private',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/{$data['prefix_url']}?metadata_private[{$metadata->name}]={$metadata->value}")
            ->assertOk()
            ->assertJsonMissing([
                $metadata->name => $metadata->value,
            ])
            ->assertJsonMissing([
                'metadata_private' => [],
            ]);
    }
}
