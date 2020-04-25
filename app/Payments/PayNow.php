<?php

namespace App\Payments;

use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayNow implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $body = [
            'amount' => $payment->amount * 100,
            'currency' => 'PLN',
            'externalId' => $payment->order->code,
            'description' => 'Zakupki na Depth',
            'buyer' => [
                'email' => $payment->order->email,
            ],
        ];

        if ($payment->continueUrl !== null) {
            $body['continueUrl'] = $payment->continueUrl;
        }

        $signature = self::hash($body, config('paynow.signature_key'));

        $response = Http::withHeaders([
            'Api-Key' => config('paynow.api_key'),
            'Signature' => $signature,
            'Idempotency-Key' => $payment->order->code . '/' . $payment->id,
            'Api-Version' => 'latest',
            'Host' => 'api.sandbox.paynow.pl',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://api.sandbox.paynow.pl/v1/payments', $body);

        $response = $response->json();

        if (isset($response['statusCode'])) {
            dd($response); // Testowo
        }

        return [
            'redirectUrl' => $response['redirectUrl'],
            'external_id' => $response['paymentId'],
            'status' => $response['status'],
        ];
    }

    protected static function hash($body, $key): string
    {
        return base64_encode(hash_hmac('sha256', json_encode($body), $key, true));
    }

    public static function translateNotification(Request $request): array
    {
        return [
            'status' => ''
        ];
    }
}
