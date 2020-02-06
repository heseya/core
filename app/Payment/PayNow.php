<?php

namespace App\Payment;

use Unirest;
use Illuminate\Http\Request;

class PayNow implements PaymentMethod
{
    protected static function hash($data, $key): string
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true));
    }

    public static function generateUrl($data): string
    {
        $body = json_encode([
            'amount' => $data['amount'],
            'description' => 'Zakupki na Depth',
            'externalId' => $data['code'],
            'buyer' => [
                'email' => $data['email'],
            ]
        ]);

        $response = Unirest\Request::post(
            'https://api.sandbox.paynow.pl/v1/payments',
            [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                'Api-Key' => config('paynow.api_key'),
                'Idempotency-Key' => $data['code'],
                'Signature' => self::hash($body, config('paynow.signature_key')),
            ],
            $body
        );

        return $response->body->redirectUrl;
    }

    public static function translateNotification(Request $request): array
    {
        return [
            'status' => ''
        ];
    }
}
