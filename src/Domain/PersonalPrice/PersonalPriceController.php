<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductPriceResource;
use Illuminate\Http\Resources\Json\JsonResource;

final class PersonalPriceController extends Controller
{
    public function __construct(
        private readonly PersonalPriceService $service,
    ) {}

    public function productPrices(ProductPricesDto $dto): JsonResource
    {
        return ProductPriceResource::collection(
            $this->service->calcProductsListDiscounts($dto),
        );
    }
}
