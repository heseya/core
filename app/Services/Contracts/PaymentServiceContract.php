<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;

interface PaymentServiceContract
{
    public function getPayment(Order $order, PaymentMethod $paymentMethod, string $continueUrl): Payment;
}
