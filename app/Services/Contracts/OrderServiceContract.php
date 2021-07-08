<?php

namespace App\Services\Contracts;

use App\Models\Order;
use Illuminate\Http\JsonResponse;

interface OrderServiceContract
{
    public function calcSummary(Order $order): float;

    public function update(array $data, Order $order): JsonResponse;
}
