<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use App\Exceptions\ClientException;
use App\Http\Controllers\Controller;
use Domain\SalesChannel\SalesChannelService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PersonalPriceController extends Controller
{
    public function __construct(
        private readonly PersonalPriceService $personalPriceService,
        private readonly SalesChannelService $saleSchannelService,
    ) {}

    /**
     * @throws ClientException
     */
    public function productPrices(Request $request, ProductPricesDto $dto): Response
    {
        return PersonalPriceDto::collection($this->personalPriceService->calcProductsListDiscounts($dto, $this->saleSchannelService->getCurrentSalesChannel()))->toResponse($request);
    }
}
