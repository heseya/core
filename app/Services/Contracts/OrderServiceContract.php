<?php

namespace App\Services\Contracts;

use App\Models\Order;

interface OrderServiceContract
{
    public function calcSummary(Order $order): float;
}
