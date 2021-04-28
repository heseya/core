<?php

namespace App\Payments;

use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayU implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $client_id = config('payu.client_id');
        $client_secret = config('payu.client_secret');

        $response = Http::post(
            config('payu.url') . "/pl/standard/user/oauth/authorize?grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret",
        )->throw();

        $amount = (int) $payment->amount * 100;

        $response = Http::withToken($response['access_token'])->withOptions([
            'allow_redirects' => false,
        ])->post(config('payu.url') . '/api/v2_1/orders', [
            'notifyUrl' => config('app.url') . '/payments/payu',
            'customerIp' => '127.0.0.1', // Posibbly enforced by website and we'll need to track order ip
            'merchantPosId' => config('payu.pos_id'),
            'description' => 'Zakupy w sklepie internetowym.',
            'currencyCode' => $payment->order->currency,
            'totalAmount' => $amount,
            'extOrderId' => $payment->getKey(),
            'returnUrl' => $payment->continue_url,
            'buyer' => [
                'email' => $payment->order->email,
            ],
            'products' => [
                [
                    'name' => 'Zakupy w sklepie internetowym.',
                    'unitPrice' => $amount,
                    'quantity' => '1',
                ],
            ],
        ])->throw();

        if ($response['status']['statusCode'] !== 'SUCCESS') {
            throw new Exception('PayU invalid status: ' . $response['status']['statusCode']);
        }

        if (!isset($response['orderId']) || !isset($response['redirectUri'])) {
            throw new Exception('PayU invalid response');
        }

        return [
            'external_id' => $response['orderId'],
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
