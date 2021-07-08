<?php

namespace App\Services\Contracts;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface OrderServiceContract
{
    public function calcSummary(Order $order): float;
    public function update(Request $request, Order $order): JsonResponse;
}
