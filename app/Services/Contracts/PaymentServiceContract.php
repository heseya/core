<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentServiceContract
{
    public function getPayment(Order $order, string $method, Request $request): Payment;
}
