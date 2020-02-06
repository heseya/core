<?php

namespace App\Payment;

use Illuminate\Http\Request;

interface PaymentMethod
{
    public static function generateUrl($data): string;

    public static function translateNotification(Request $request);
}
