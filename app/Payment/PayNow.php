<?php

namespace App\Payment;

use Unirest;
use App\Payment;
use Illuminate\Http\Request;

class PayNow implements PaymentMethod
{
    protected static function hash($body, $key): string
    {
        return base64_encode(hash_hmac('sha256', $body, $key, true));
    }

    public static function generateUrl(Payment $payment): array
    {
        $body = json_encode([
            'amount' => $payment->amount,
            'currency' => 'PLN',
            'externalId' => $payment->order->code,
            'description' => 'Zakupki na Depth',
            'buyer' => [
                'email' => $payment->order->email,
            ]
        ]);

        $response = Unirest\Request::post(
            'https://api.sandbox.paynow.pl/v1/payments',
            [
                'Api-Key' => config('paynow.api_key'),
                'Signature' => self::hash($body, config('paynow.signature_key')),
                'Idempotency-Key' => $payment->id,
                'Api-Version' => 'latest',
                'Host' => 'api.sandbox.paynow.pl',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            $body
        );

        dd($response);

        return [
            'url' => $response->body->redirectUrl,
            'external_id' => $response->body->paymentId,
            'status' => $response->body->status,
        ];
    }

    public static function translateNotification(Request $request): array
    {
        return [
            'status' => ''
        ];
    }
}
