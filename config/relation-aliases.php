<?php

use App\Enums\RelationAlias;
use App\Models\App;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Role;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\User;
use Domain\Banner\Models\Banner;
use Domain\Page\Page;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductSet\ProductSet;

return [
    RelationAlias::APP->value => App::class,
    RelationAlias::ATTRIBUTE->value => Attribute::class,
    RelationAlias::ATTRIBUTE_OPTION->value => AttributeOption::class,
    RelationAlias::BANNER->value => Banner::class,
    RelationAlias::DISCOUNT->value => Discount::class,
    RelationAlias::ITEM->value => Item::class,
    RelationAlias::MEDIA->value => Media::class,
    RelationAlias::OPTION->value => Option::class,
    RelationAlias::ORDER->value => Order::class,
    RelationAlias::ORDER_PRODUCT->value => OrderProduct::class,
    RelationAlias::PAGE->value => Page::class,
    RelationAlias::PRODUCT->value => Product::class,
    RelationAlias::PRODUCT_SET->value => ProductSet::class,
    RelationAlias::ROLE->value => Role::class,
    RelationAlias::SCHEMA->value => Schema::class,
    RelationAlias::SHIPPING_METHOD->value => ShippingMethod::class,
    RelationAlias::STATUS->value => Status::class,
    RelationAlias::USER->value => User::class,
];
