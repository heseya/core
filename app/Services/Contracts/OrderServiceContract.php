<?php

namespace App\Services\Contracts;

use App\Dtos\CartDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderIndexDto;
use App\Dtos\OrderProductSearchDto;
use App\Dtos\OrderProductUpdateDto;
use App\Dtos\OrderUpdateDto;
use App\Models\CartResource;
use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

interface OrderServiceContract
{
    public function calcSummary(Order $order): float;

    public function store(OrderDto $dto): Order;

    public function update(OrderUpdateDto $dto, Order $order): JsonResponse;

    public function indexUserOrder(OrderIndexDto $dto): LengthAwarePaginator;

    public function cartProcess(CartDto $cartDto): CartResource;

    public function processOrderProductUrls(OrderProductUpdateDto $dto, OrderProduct $product): OrderProduct;

    public function indexMyOrderProducts(OrderProductSearchDto $dto): LengthAwarePaginator;

    public function sendUrls(Order $order): void;

    public function shippingList(Order $order, string $packageTemplateId): Order;
}
