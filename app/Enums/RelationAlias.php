<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum RelationAlias: string
{
    use EnumTrait;

    case APP = 'App';
    case ATTRIBUTE = 'Attribute';
    case ATTRIBUTE_OPTION = 'AttributeOption';
    case BANNER = 'Banner';
    case DISCOUNT = 'Discount';
    case ITEM = 'Item';
    case MEDIA = 'Media';
    case OPTION = 'Option';
    case ORDER = 'Order';
    case ORDER_PRODUCT = 'OrderProduct';
    case PAGE = 'Page';
    case PRODUCT = 'Product';
    case PRODUCT_SET = 'ProdcutSet';
    case ROLE = 'Role';
    case SCHEMA = 'Schema';
    case SHIPPING_METHOD = 'ShippingMethod';
    case STATUS = 'Status';
    case USER = 'User';
}
