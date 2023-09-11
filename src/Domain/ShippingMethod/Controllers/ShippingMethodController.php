<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShippingMethodIndexRequest;
use App\Http\Requests\ShippingMethodReorderRequest;
use App\Http\Resources\ShippingMethodResource;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\ShippingMethod\Dtos\ShippingMethodCreateDto;
use Domain\ShippingMethod\Dtos\ShippingMethodUpdateDto;
use Domain\ShippingMethod\Models\ShippingMethod;
use Domain\ShippingMethod\Services\Contracts\ShippingMethodServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

final class ShippingMethodController extends Controller
{
    public function __construct(
        private readonly ShippingMethodServiceContract $shippingMethodService,
    ) {}

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function index(ShippingMethodIndexRequest $request): JsonResource
    {
        $cartTotal = $request->input('cart_value') ? Money::of(
            $request->input('cart_value.value'),
            $request->input('cart_value.currency'),
        ) : null;

        $shippingMethods = $this->shippingMethodService->index(
            $request->only('metadata', 'metadata_private', 'ids'),
            $request->input('country'),
            $cartTotal,
        );

        return ShippingMethodResource::collection($shippingMethods);
    }

    public function store(ShippingMethodCreateDto $dto): JsonResource
    {
        $shippingMethod = $this->shippingMethodService->store($dto);

        return ShippingMethodResource::make($shippingMethod);
    }

    public function update(ShippingMethodUpdateDto $dto, ShippingMethod $shippingMethod): JsonResource
    {
        $shippingMethod = $this->shippingMethodService->update(
            $shippingMethod,
            $dto,
        );

        return ShippingMethodResource::make($shippingMethod);
    }

    public function reorder(ShippingMethodReorderRequest $request): JsonResponse
    {
        $this->shippingMethodService->reorder($request->input('shipping_methods'));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function destroy(ShippingMethod $shippingMethod): JsonResponse
    {
        $this->shippingMethodService->destroy($shippingMethod);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
