<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Services\Contracts;

use Brick\Money\Money;
use Domain\ShippingMethod\Dtos\ShippingMethodCreateDto;
use Domain\ShippingMethod\Dtos\ShippingMethodUpdateDto;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ShippingMethodServiceContract
{
    /**
     * @param array<string, string>|null $search
     *
     * @return LengthAwarePaginator<ShippingMethod>
     */
    public function index(?array $search, ?string $country, ?Money $cartValue): LengthAwarePaginator;

    public function store(ShippingMethodCreateDto $shippingMethodDto): ShippingMethod;

    public function update(ShippingMethod $shippingMethod, ShippingMethodUpdateDto $shippingMethodDto): ShippingMethod;

    /**
     * @param array<ShippingMethod> $shippingMethods
     */
    public function reorder(array $shippingMethods): void;

    public function destroy(ShippingMethod $shippingMethod): void;
}
