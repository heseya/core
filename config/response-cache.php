<?php

use App\Enums\CacheTime;

return [
    'auth' => [
        'GET' => [
            // Long time
            '/shipping-methods' => CacheTime::LONG_TIME->value,
            '/pages' => CacheTime::LONG_TIME->value,
            '/product-sets' => CacheTime::LONG_TIME->value,
            '/product-sets/' => CacheTime::LONG_TIME->value,
            '/settings' => CacheTime::LONG_TIME->value,
            // short time
            '/banners' => CacheTime::SHORT_TIME->value,
            // very short time
            '/products' => CacheTime::VERY_SHORT_TIME->value,
        ],
    ],
    'public' => [
        'GET' => [
            // long time
            '/countries' => CacheTime::LONG_TIME->value,
            '/payment-methods' => CacheTime::LONG_TIME->value,
            '/pages/' => CacheTime::LONG_TIME->value,
            '/seo' => CacheTime::LONG_TIME->value,
            // short time
            '/consents' => CacheTime::SHORT_TIME->value,
            '/permissions' => CacheTime::SHORT_TIME->value,
            '/filters' => CacheTime::SHORT_TIME->value,
        ],
    ],
];
