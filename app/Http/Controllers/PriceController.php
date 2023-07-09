<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductPricesRequest;
use App\Http\Resources\ProductPriceResource;
use App\Services\Contracts\PriceServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceController extends Controller
{
    public function __construct(
        private readonly PriceServiceContract $priceService,
    ) {}

    public function productPrices(ProductPricesRequest $request): JsonResource
    {
        $productIds = $request->input('ids', []);

        return ProductPriceResource::collection(
            $this->priceService->calcProductsListDiscounts($productIds),
        );
    }
}
