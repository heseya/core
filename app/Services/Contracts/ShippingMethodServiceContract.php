<?php

namespace App\Services\Contracts;

use App\Http\Requests\ShippingMethodIndexRequest;
use App\Http\Requests\ShippingMethodOrderRequest;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface ShippingMethodServiceContract
{
    public function index(ShippingMethodIndexRequest $request): JsonResource;

    public function store(ShippingMethodStoreRequest $request): JsonResource;

    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shippingMethod): JsonResource;

    public function order(ShippingMethodOrderRequest $request): JsonResponse;

    public function destroy(ShippingMethod $shippingMethod): JsonResponse;
}
