<?php

namespace Support\LaravelData;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\PaginatedDataCollection;

trait ExtendedData
{
    public static function paginatedCollection(Paginator $items): PaginatedDataCollection
    {
        return new (static::$_paginatedCollectionClass)(static::class, $items);
    }

    public static function staticCollection(Enumerable|array|DataCollection $items): DataCollection
    {
        return new (static::$_collectionClass)(static::class, $items);
    }
}
