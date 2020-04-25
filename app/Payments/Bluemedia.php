<?php

namespace App\Payments;

use App\Payment;
use Illuminate\Http\Request;

class Bluemedia implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $amount = number_format($payment->amount, 2, '.', '');

        $hash = hash('sha256',
            config('bluemedia.service_id') . '|' .
            $payment->id . '|' .
            $amount . '|' .
            config('bluemedia.key'),
        );

        $url = 'https://pay.bm.pl/payment' .
            '?ServiceID=' . config('bluemedia.service_id') .
            '&OrderID=' . $payment->id .
            '&Amount=' . $amount .
            '&Hash=' . $hash;

        return [
            'redirectUrl' => $url,
        ];
    }

    public static function translateNotification(Request $request): array
    {
        return [
            'status' => ''
        ];
    }
}
