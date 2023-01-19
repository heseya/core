<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

interface PaymentServiceContract
{
    public function getPayment(Order $order, PaymentMethod $paymentMethod, Request $request): Payment;
}
