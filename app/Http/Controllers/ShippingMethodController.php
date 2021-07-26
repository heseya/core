<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\ShippingMethodControllerSwagger;
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

class ShippingMethodController extends Controller implements ShippingMethodControllerSwagger
{
    private ShippingMethodServiceContract $shippingMethodServiceContract;

    public function __construct(ShippingMethodServiceContract $shippingMethodServiceContract)
    {
        $this->shippingMethodServiceContract = $shippingMethodServiceContract;
    }

    public function index(ShippingMethodIndexRequest $request): JsonResource
    {
        $shippingMethods = $this->shippingMethodServiceContract->index(
            $request->input('country'),
            $request->input('cart_value', 0)
        );

        return ShippingMethodResource::collection($shippingMethods);
    }

    public function store(ShippingMethodStoreRequest $request): JsonResource
    {
        $shippingMethod = $this->shippingMethodServiceContract->store($request);

        return ShippingMethodResource::make($shippingMethod);
    }

    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shippingMethod): JsonResource
    {
        $shippingMethod = $this->shippingMethodServiceContract->update($request, $shippingMethod);

        return ShippingMethodResource::make($shippingMethod);
    }

    public function reorder(ShippingMethodReorderRequest $request): JsonResponse
    {
        $this->shippingMethodServiceContract->reorder($request->input('shipping_methods'));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function destroy(ShippingMethod $shippingMethod): JsonResponse
    {
        $this->shippingMethodServiceContract->destroy($shippingMethod);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
