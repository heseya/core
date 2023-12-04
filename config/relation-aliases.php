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
use App\Models\PriceMap;
use App\Models\Product;
use App\Models\Role;
use App\Models\Schema;
use App\Models\Status;
use App\Models\User;
use App\Models\WebHook;
use Domain\App\Models\AppWidget;
use Domain\Banner\Models\Banner;
use Domain\Organization\Models\Organization;
use Domain\Page\Page;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Domain\ShippingMethod\Models\ShippingMethod;

return [
    'App' => App::class,
    'AppWidget' => AppWidget::class,
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
    'Organization' => Organization::class,
    'PriceMap' => PriceMap::class,
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
];
