<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use App\Exceptions\ClientException;
use App\Http\Controllers\Controller;
use Support\ResourceDto;

final class PersonalPriceController extends Controller
{
    public function __construct(
        private readonly PersonalPriceService $service,
    ) {}

    /**
     * @throws ClientException
     */
    public function productPrices(ProductPricesDto $dto): ResourceDto
    {
        return new ResourceDto(
            $this->service->calcProductsListDiscounts($dto),
        );
    }
}
