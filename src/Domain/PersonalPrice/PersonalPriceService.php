<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use App\Dtos\ProductPriceDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Product;
use App\Services\Contracts\DiscountServiceContract;
use Illuminate\Support\Facades\Gate;

final readonly class PersonalPriceService
{
    public function __construct(
        private DiscountServiceContract $discountService,
    ) {}

    /**
     * @return ProductPriceDto[]
     *
     * @throws ClientException
     */
    public function calcProductsListDiscounts(ProductPricesDto $dto): array
    {
        $query = Product::query()->whereIn('id', $dto->ids);

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        $products = $query->get();

        if ($products->count() < count($dto->ids)) {
            throw new ClientException(Exceptions::PRODUCT_NOT_FOUND);
        }

        return $this->discountService->calcProductsListDiscounts($products);
    }
}
