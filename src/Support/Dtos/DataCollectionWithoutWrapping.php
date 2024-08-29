<?php

declare(strict_types=1);

namespace Support\Dtos;

use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends DataCollection<TKey, TValue>
 */
final class DataCollectionWithoutWrapping extends DataCollection
{
    /**
     * @param class-string<TValue> $dataClass
     * @param array<TKey, TValue>|Enumerable<TKey, TValue>|DataCollection<TKey, TValue> $items
     */
    public function __construct(
        string $dataClass,
        array|DataCollection|Enumerable|null $items,
    ) {
        parent::__construct($dataClass, $items);

        $this->withoutWrapping();
    }
}
