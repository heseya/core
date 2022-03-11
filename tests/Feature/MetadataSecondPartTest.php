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

class MetadataSecondPartTest extends MetadataFirstPartTest
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function dataProvider(): array
    {
        return [
            'statuses as user' => ['user', ['model' => Status::class, 'prefix_url' => 'statuses', 'role' => 'statuses.edit']],
            'statuses as application' => ['application', ['model' => Status::class, 'prefix_url' => 'statuses', 'role' => 'statuses.edit']],

            'shipping methods as user' => ['user', ['model' => ShippingMethod::class, 'prefix_url' => 'shipping-methods', 'role' => 'shipping_methods.edit']],
            'shipping methods as application' => ['application', ['model' => ShippingMethod::class, 'prefix_url' => 'shipping-methods', 'role' => 'shipping_methods.edit']],

            'package templates as user' => ['user', ['model' => PackageTemplate::class, 'prefix_url' => 'package-templates', 'role' => 'packages.edit']],
            'package templates as application' => ['application', ['model' => PackageTemplate::class, 'prefix_url' => 'package-templates', 'role' => 'packages.edit']],

            'users as user' => ['user', ['model' => User::class, 'prefix_url' => 'users', 'role' => 'users.edit']],
            'users as application' => ['application', ['model' => User::class, 'prefix_url' => 'users', 'role' => 'users.edit']],

            'roles as user' => ['user', ['model' => Role::class, 'prefix_url' => 'roles', 'role' => 'roles.edit']],
            'roles as application' => ['application', ['model' => Role::class, 'prefix_url' => 'roles', 'role' => 'roles.edit']],

            'pages as user' => ['user', ['model' => Page::class, 'prefix_url' => 'pages', 'role' => 'pages.edit']],
            'pages as application' => ['application', ['model' => Page::class, 'prefix_url' => 'pages', 'role' => 'pages.edit']],

            'apps as user' => ['user', ['model' => App::class, 'prefix_url' => 'apps', 'role' => 'apps.install']],
            'apps as application' => ['application', ['model' => App::class, 'prefix_url' => 'apps', 'role' => 'apps.install']],

            'media as user' => ['user', ['model' => Media::class, 'prefix_url' => 'media', 'role' => 'products.edit']],
            'media as application' => ['application', ['model' => Media::class, 'prefix_url' => 'media', 'role' => 'products.edit']],
        ];
    }
    /**
     * @dataProvider dataProvider
     */
    public function testAddMetadata($user, $data): void
    {
        parent::testAddMetadata($user, $data);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAddMetadataPrivate($user, $data): void
    {
        parent::testAddMetadataPrivate($user, $data);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUpdateMetadata($user, $data): void
    {
        parent::testUpdateMetadata($user, $data);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUpdateMetadataPrivate($user, $data): void
    {
        parent::testUpdateMetadataPrivate($user, $data);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDeleteMetadata($user, $data): void
    {
        parent::testDeleteMetadata($user, $data);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDeleteMetadataPrivate($user, $data): void
    {
        parent::testDeleteMetadataPrivate($user, $data);
    }
}
