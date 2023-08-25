<?php

use App\Models\Discount;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Models\Status;
use Domain\Banner\Models\BannerMedia;
use Domain\Consent\Models\Consent;
use Domain\Page\Page;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;

return [

    /*
     * If a translation has not been set for a given locale, use this locale instead.
     */
    'fallback_locale' => null,

    /*
     * If a translation has not been set for a given locale and the fallback locale,
     * any other locale will be chosen instead.
     */
    'fallback_any' => false,

    'models' => [
        Discount::class => ['name', 'description', 'description_html'],
        BannerMedia::class => ['title', 'subtitle'],
        SalesChannel::class => ['name'],
        AttributeOption::class => ['name'],
        ProductSet::class => ['name', 'description_html'],
        Option::class => ['name'],
        Product::class => ['name', 'description_html', 'description_short'],
        Schema::class => [ 'name', 'description'],
        Consent::class => ['name', 'description_html'],
        Attribute::class => ['name', 'description'],
        Status::class => ['name', 'description'],
        Page::class => ['name', 'content_html'],
    ],
];
