<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\ShippingMethodControllerSwagger;
use App\Http\Requests\ShippingMethodIndexRequest;
use App\Http\Requests\ShippingMethodOrderRequest;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Models\ShippingMethod;
use App\Services\Contracts\ShippingMethodServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodController extends Controller implements ShippingMethodControllerSwagger
{
    private ShippingMethodServiceContract $shippingMethodServiceContract;

    public function __construct(ShippingMethodServiceContract $shippingMethodServiceContract)
    {
        $this->shippingMethodServiceContract = $shippingMethodServiceContract;
    }

    public function index(ShippingMethodIndexRequest $request): JsonResource
    {
        return $this->shippingMethodServiceContract->index($request);
    }

    public function store(ShippingMethodStoreRequest $request): JsonResource
    {
        return $this->shippingMethodServiceContract->store($request);
    }

    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shippingMethod): JsonResource
    {
        return $this->shippingMethodServiceContract->update($request, $shippingMethod);
    }

    public function order(ShippingMethodOrderRequest $request): JsonResponse
    {
        return $this->shippingMethodServiceContract->order($request);
    }

    public function destroy(ShippingMethod $shippingMethod): JsonResponse
    {
        return $this->shippingMethodServiceContract->destroy($shippingMethod);
    }
}
