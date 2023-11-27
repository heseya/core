<?php

use App\Models\App;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Schema;
use App\Models\Status;
use App\Models\User;
use App\Models\WebHook;
use Domain\Banner\Models\Banner;
use Domain\Page\Page;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Domain\ShippingMethod\Models\ShippingMethod;

return [
    'App' => App::class,
    'Attribute' => Attribute::class,
    'AttributeOption' => AttributeOption::class,
    'Banner' => Banner::class,
    'Discount' => Discount::class,
    'DiscountCondition' => DiscountCondition::class,
    'Item' => Item::class,
    'Media' => Media::class,
    'Option' => Option::class,
    'Order' => Order::class,
    'OrderProduct' => OrderProduct::class,
    'Page' => Page::class,
    'Permission' => Permission::class,
    'Product' => Product::class,
    'ProductSet' => ProductSet::class,
    'Role' => Role::class,
    'Schema' => Schema::class,
    'ShippingMethod' => ShippingMethod::class,
    'Status' => Status::class,
    'User' => User::class,
    'WebHook' => WebHook::class,

    // old aliases
    'App\\Models\\App' => App::class,
    'App\\Models\\Attribute' => Attribute::class,
    'App\\Models\\AttributeOption' => AttributeOption::class,
    'App\\Models\\Banner' => Banner::class,
    'App\\Models\\Discount' => Discount::class,
    'App\\Models\\DiscountCondition' => DiscountCondition::class,
    'App\\Models\\Item' => Item::class,
    'App\\Models\\Media' => Media::class,
    'App\\Models\\Option' => Option::class,
    'App\\Models\\Order' => Order::class,
    'App\\Models\\OrderProduct' => OrderProduct::class,
    'App\\Models\\Page' => Page::class,
    'App\\Models\\Permission' => Permission::class,
    'App\\Models\\Product' => Product::class,
    'App\\Models\\ProductSet' => ProductSet::class,
    'App\\Models\\Role' => Role::class,
    'App\\Models\\Schema' => Schema::class,
    'App\\Models\\ShippingMethod' => ShippingMethod::class,
    'App\\Models\\Status' => Status::class,
    'App\\Models\\User' => User::class,
    'App\\Models\\WebHook' => WebHook::class,
];
