<?php

namespace App\Repositories\Contracts;

use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\Product\Dtos\ProductSearchDto;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/** @see \App\Repositories\ProductRepository */
interface ProductRepositoryContract
{
    public function search(ProductSearchDto $dto): LengthAwarePaginator;

    /**
     * @param array<string, PriceDto[]> $priceMatrix
     */
    public function setProductPrices(string $productId, array $priceMatrix): void;

    /**
     * @param ProductPriceType[] $priceTypes
     *
     * @return Collection|EloquentCollection<string,Collection<int,PriceDto>|EloquentCollection<int,PriceDto>>
     *
     * @throws DtoException
     */
    public function getProductPrices(
        string $productId,
        array $priceTypes,
        ?Currency $currency = null,
    ): Collection|EloquentCollection;
}
