<?php

declare(strict_types=1);

namespace Application\PriceMap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Application\PriceMap\Requests\PriceMapListPricesRequest;
use Domain\PriceMap\Dtos\PriceMapCreateDto;
use Domain\PriceMap\Dtos\PriceMapPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapProductPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapSchemaPricesUpdateDto;
use Domain\PriceMap\Dtos\PriceMapUpdateDto;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapService;
use Domain\PriceMap\Resources\PriceMapProductPriceData;
use Domain\PriceMap\Resources\PriceMapSchemaPricesDataCollection;
use Domain\Product\Dtos\ProductSearchDto;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class PriceMapController extends Controller
{
    public function __construct(
        private PriceMapService $priceMapService,
    ) {}

    public function index(Request $request): HttpResponse
    {
        return $this->priceMapService->list()->toResponse($request);
    }

    public function store(Request $request, PriceMapCreateDto $dto): HttpResponse
    {
        return $this->priceMapService->create($dto)->getData()->toResponse($request);
    }

    public function update(Request $request, PriceMap $priceMap, PriceMapUpdateDto $dto): HttpResponse
    {
        return $this->priceMapService->update($priceMap, $dto)->getData()->toResponse($request);
    }

    public function destroy(PriceMap $priceMap): HttpResponse
    {
        $this->priceMapService->delete($priceMap);

        return Response::noContent();
    }

    public function searchPrices(PriceMapListPricesRequest $request, PriceMap $priceMap): HttpResponse
    {
        return $this->priceMapService->searchPrices($priceMap, ProductSearchDto::from($request))->toResponse($request);
    }

    public function updatePrices(Request $request, PriceMap $priceMap, PriceMapPricesUpdateDto $dto): HttpResponse
    {
        return $this->priceMapService->updatePrices($priceMap, $dto)->toResponse($request);
    }

    public function listProductPrices(Request $request, Product $product): HttpResponse
    {
        return PriceMapProductPriceData::collection($product->mapPrices)->toResponse($request);
    }

    public function updateProductPrices(Request $request, Product $product, PriceMapProductPricesUpdateDto $dto): HttpResponse
    {
        return $this->priceMapService->updateProductPrices($product, $dto)->toResponse($request);
    }

    public function listSchemaPrices(Request $request, Schema $schema): HttpResponse
    {
        return PriceMapSchemaPricesDataCollection::fromSchema($schema)->toResponse($request);
    }

    public function updateSchemaPrices(Request $request, Schema $schema, PriceMapSchemaPricesUpdateDto $dto): HttpResponse
    {
        return $this->priceMapService->updateSchemaPrices($schema, $dto)->toResponse($request);
    }
}
