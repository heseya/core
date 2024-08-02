<?php

use App\Models\Discount;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema as DeprecatedSchema;
use App\Models\Status;
use Domain\Banner\Models\BannerMedia;
use Domain\Consent\Models\Consent;
use Domain\Page\Page;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\Seo\Models\SeoMetadata;
use Domain\Tag\Models\Tag;

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
        Discount::class,
        BannerMedia::class,
        SalesChannel::class,
        AttributeOption::class,
        ProductSet::class,
        Option::class,
        Product::class,
        DeprecatedSchema::class,
        Schema::class,
        Consent::class,
        Attribute::class,
        Status::class,
        Page::class,
        Tag::class,
        SeoMetadata::class,
    ],
];
