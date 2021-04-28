<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Srmklive\PayPal\Facades\PayPal as PayPalMethod;

class PayPal implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $provider = PayPalMethod::setProvider();
        $provider->setApiCredentials(config('paypal'));
        $provider->setAccessToken($provider->getAccessToken());

        $response = $provider->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $payment->order->currency,
                        'value' => $payment->amount,
                    ],
                ],
            ],
        ]);

        return [
            'redirect_url' => $response['links'][1]['href'],
        ];
    }

    public static function translateNotification(Request $request)
    {
        // Optional signature verification; lackluster docs
        // $signature = $request->header('OpenPayu-Signature');

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.status' => ['required', 'string'],
            'order.extOrderId' => ['required', 'string', 'exists:payments,id'],
        ]);

        $order = $validated['order'];
        $status = $order['status'];

        $payment = Payment::findOrFail($order['extOrderId']);

        if ($status === 'COMPLETED') {
            $payment->update([
                'payed' => true,
            ]);
        }

        return response()->json(null, 200);
    }
}
