<?php

namespace App\Services\Contracts;

use App\Http\Requests\ShippingMethodIndexRequest;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Collection;

interface ShippingMethodServiceContract
{
    public function index(ShippingMethodIndexRequest $request): Collection;

    public function store(ShippingMethodStoreRequest $request): ShippingMethod;

    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shippingMethod): ShippingMethod;

    public function reorder(array $shippingMethods): void;

    public function destroy(ShippingMethod $shippingMethod): void;
}
