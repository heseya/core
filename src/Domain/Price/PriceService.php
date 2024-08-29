<?php

declare(strict_types=1);

namespace Domain\Price;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use App\Models\Price;
use App\Models\Product;
use Domain\Currency\Currency;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Domain\Price\Dtos\ProductCachedPricesDto;
use Domain\Price\Dtos\ProductCachedPricesDtoCollection;
use Domain\Price\Enums\ProductPriceType;
use Domain\SalesChannel\Models\SalesChannel;
use Heseya\Dto\DtoException;
use Illuminate\Support\Collection;
use Support\Dtos\ModelIdentityDto;

final readonly class PriceService
{
    public function __construct(
        private readonly PriceRepository $priceRepository,
    ) {}

    /**
     * @param ProductCachedPricesDtoCollection|ProductCachedPricesDto[]|array<value-of<ProductPriceType>,ProductCachedPriceDto[]> $priceMatrix
     */
    public function setCachedProductPrices(Product|string $product, array|ProductCachedPricesDtoCollection $priceMatrix): void
    {
        $this->priceRepository->setCachedProductPrices(
            $product instanceof Product ? $product : new ModelIdentityDto($product, (new Product())->getMorphClass()),
            $priceMatrix,
        );
    }

    /**
     * @param ProductPriceType[] $priceTypes
     *
     * @return Collection<string,Collection<int,ProductCachedPriceDto>>
     *
     * @throws DtoException
     * @throws ServerException
     */
    public function getCachedProductPrices(
        Product|string $product,
        array $priceTypes,
        Currency|SalesChannel|null $filter = null,
        bool $throw = true,
    ): Collection {
        $prices = $this->priceRepository->getModelPrices(
            $product instanceof Product ? $product : new ModelIdentityDto($product, (new Product())->getMorphClass()),
            $priceTypes,
            $filter,
        );

        $groupedPrices = $prices->collect()->mapToGroups(fn (Price $price) => [$price->price_type => ProductCachedPriceDto::from($price)]);

        if ($throw) {
            foreach ($priceTypes as $type) {
                if (!$groupedPrices->has($type->value)) {
                    throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
                }
            }
        }

        return $groupedPrices;
    }
}
