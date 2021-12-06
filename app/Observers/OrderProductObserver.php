<?php

namespace App\Observers;

use App\Models\OrderProduct;
use App\Services\Contracts\OrderServiceContract;
use App\Services\OrderService;

class OrderProductObserver
{
    public function created(OrderProduct $orderProduct)
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderServiceContract::class);

        $orderProduct->order()->update([
            'summary' => $orderService->calcSummary($orderProduct->order),
        ]);
    }
}
