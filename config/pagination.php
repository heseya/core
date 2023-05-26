<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    /*
    |--------------------------------------------------------------------------
    | Limit Key
    |--------------------------------------------------------------------------
    |
    | Key you use in url to set pagination limit.
    |
    */
    'limit_key' => Env::get('PAGINATION_LIMIT_KEY', 'limit'),

    /*
    |--------------------------------------------------------------------------
    | Pagination default
    |--------------------------------------------------------------------------
    |
    | Default pagination limit, when no pagination was found in request.
    |
    */
    'per_page' => (int) Env::get('PAGINATION_DEFAULT', 24),

    /*
    |--------------------------------------------------------------------------
    | Pagination max
    |--------------------------------------------------------------------------
    |
    | Max pagination limit.
    |
    */
    'max' => (int) Env::get('PAGINATION_MAX', 500),
];
