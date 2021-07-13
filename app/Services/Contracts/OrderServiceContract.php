<?php

namespace App\Services\Contracts;

use App\Dtos\OrderUpdateDto;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

interface OrderServiceContract
{
    public function calcSummary(Order $order): float;

    public function update(OrderUpdateDto $dto, Order $order): JsonResponse;
}
