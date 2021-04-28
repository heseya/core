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
            'external_id' => $response['id'],
            'redirect_url' => $response['links'][1]['href'],
        ];
    }

    public static function translateNotification(Request $request)
    {
         $request->validate([
            'txn_id' => ['required', 'string'],
            'payment_status' => ['required', 'string'],
            'mc_gross' => ['required'],
         ]);

        $payment = Payment::where('external_id', $request->input('txn_id'))->firstOrFail();

        if (
            $request->input('payment_status') === 'Completed' &&
            $request->input('mc_gross') === number_format($payment->order->amount, 2, '.', '')
        ) {
            $payment->update([
                'payed' => true,
            ]);

            return response()->json(null);
        }

        return response()->json(null, 400);
    }
}
