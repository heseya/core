<?php

namespace App\Http\Controllers;

use App\Dtos\ShippingMethodCreateDto;
use App\Dtos\ShippingMethodUpdateDto;
use App\Http\Requests\ShippingMethodIndexRequest;
use App\Http\Requests\ShippingMethodReorderRequest;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Http\Resources\ShippingMethodResource;
use App\Models\ShippingMethod;
use App\Services\Contracts\ShippingMethodServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class ShippingMethodController extends Controller
{
    public function __construct(
        private ShippingMethodServiceContract $shippingMethodService,
    ) {
    }

    public function index(ShippingMethodIndexRequest $request): JsonResource
    {
        $shippingMethods = $this->shippingMethodService->index(
            $request->only('metadata', 'metadata_private'),
            $request->input('country'),
            $request->input('cart_value', 0),
        );

        return ShippingMethodResource::collection($shippingMethods);
    }

    public function store(ShippingMethodStoreRequest $request): JsonResource
    {
        $shippingMethod = $this->shippingMethodService->store(
            ShippingMethodCreateDto::instantiateFromRequest($request),
        );

        return ShippingMethodResource::make($shippingMethod);
    }

    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shippingMethod): JsonResource
    {
        $shippingMethod = $this->shippingMethodService->update(
            $shippingMethod,
            ShippingMethodUpdateDto::instantiateFromRequest($request),
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
