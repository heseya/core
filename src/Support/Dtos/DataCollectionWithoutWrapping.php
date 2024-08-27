<?php

declare(strict_types=1);

namespace Support\Dtos;

use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;

class DataCollectionWithoutWrapping extends DataCollection
{
    public function __construct(
        string $dataClass,
        Enumerable|array|DataCollection|null $items,
    ) {
        parent::__construct($dataClass, $items);

        $this->withoutWrapping();
    }
}
