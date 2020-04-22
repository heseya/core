<?php

namespace App\Payment;

use Unirest;
use Illuminate\Http\Request;

class Cinkciarz implements PaymentMethod
{
    protected static function getCinkciarzToken()
    {
        $response = Unirest\Request::post(
            config('cinkciarz.host') . '/connect/token',
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => config('cinkciarz.client_id') . ':' . config('cinkciarz.client_secret'),
            ],
            Unirest\Request\Body::json([
                'grant_type' => 'client_credentials',
                'scope' => 'pay_api',
            ])
        );

        dd($response);
    }

    public static function generateUrl(): string
    {
        $token = self::getCinkciarzToken();

        return '';
    }

    public static function translateNotification(Request $request): array
    {
        return [
            'status' => ''
        ];
    }
}
