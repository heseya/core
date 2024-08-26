<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Product;
use App\Services\DiscountService;
use Domain\Price\Resources\ProductCachedPriceData;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final readonly class PersonalPriceService
{
    public function __construct(
        private DiscountService $discountService,
    ) {}

    /**
     * @return Collection<int,ProductCachedPriceData>
     *
     * @throws ClientException
     */
    public function calcProductsListDiscounts(ProductPricesDto $dto, SalesChannel $salesChannel): Collection
    {
        $query = Product::query()->whereIn('id', $dto->ids);

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        $products = $query->get();

        if ($products->count() < count($dto->ids)) {
            throw new ClientException(Exceptions::PRODUCT_NOT_FOUND);
        }

        return $this->discountService->calcProductsListDiscounts($products, $salesChannel);
    }
}
