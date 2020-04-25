<?php

namespace App\Payments;

use App\Payment;
use Illuminate\Http\Request;

interface PaymentMethod
{
    public static function generateUrl(Payment $payment): array;

    public static function translateNotification(Request $request);
}
