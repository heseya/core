<?php

namespace App\Services\Contracts;

use App\Dtos\ShippingMethodDto;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Collection;

interface ShippingMethodServiceContract
{
    public function index(?string $country, float $cartValue): Collection;

    public function store(ShippingMethodDto $shippingMethodDto): ShippingMethod;

    public function update(ShippingMethod $shippingMethod, ShippingMethodDto $shippingMethodDto): ShippingMethod;

    public function reorder(array $shippingMethods): void;

    public function destroy(ShippingMethod $shippingMethod): void;
}
