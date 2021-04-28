<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPal implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $client_id = config('paypal.client_id');
        $secret = config('paypal.secret');

//        $response = Http::withToken($client_id . ':' . $secret, 'Basic')
//            ->withHeaders([
//                'Accept' => 'application/json',
//            ])
//            ->asForm()
//            ->post(config('paypal.url') . '/v1/oauth2/token', [
//                'grant_type' => 'client_credentials'
//            ]);
//
//        dd($response->body());

        $response = Http::withToken(base64_encode($client_id . ':' . $secret), 'Basic')
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post(config('paypal.url') . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $payment->order->currency,
                            'value' => $payment->amount,
                        ],
                    ],
                ],
            ])->throw();

        dd($response);

        return [
            'redirect_url' => $response['redirectUri'],
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
