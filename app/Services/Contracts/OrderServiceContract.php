<?php

namespace App\Services\Contracts;

use App\Dtos\CartDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderIndexDto;
use App\Models\CartResource;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

interface OrderServiceContract
{
    public function calcSummary(Order $order): float;

    public function store(OrderDto $dto): Order;

    public function update(OrderDto $dto, Order $order): JsonResponse;

    public function indexUserOrder(OrderIndexDto $dto): LengthAwarePaginator;

    public function cartProcess(CartDto $cartDto): CartResource;
}
