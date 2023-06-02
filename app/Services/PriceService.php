<?php

namespace App\Services;

use App\Dtos\ProductPriceDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Product;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\PriceServiceContract;
use Illuminate\Support\Facades\Gate;

readonly class PriceService implements PriceServiceContract
{
    public function __construct(
        private DiscountServiceContract $discountService,
    ) {
    }

    /**
     * @return ProductPriceDto[]
     *
     * @throws ClientException
     */
    public function calcProductsListDiscounts(array $productIds): array
    {
        $query = Product::query()->whereIn('id', $productIds);

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        $products = $query->get();

        if ($products->count() < count($productIds)) {
            throw new ClientException(Exceptions::PRODUCT_NOT_FOUND);
        }

        return $this->discountService->calcProductsListDiscounts($products);
    }
}
