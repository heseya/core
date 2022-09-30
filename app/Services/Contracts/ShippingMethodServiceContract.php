<?php

namespace App\Services\Contracts;

use App\Dtos\ShippingMethodDto;
use App\Models\ShippingMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ShippingMethodServiceContract
{
    public function index(?array $search, ?string $country, float $cartValue): LengthAwarePaginator;

    public function store(ShippingMethodDto $shippingMethodDto): ShippingMethod;

    public function update(ShippingMethod $shippingMethod, ShippingMethodDto $shippingMethodDto): ShippingMethod;

    public function reorder(array $shippingMethods): void;

    public function destroy(ShippingMethod $shippingMethod): void;
}
