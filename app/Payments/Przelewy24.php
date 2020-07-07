<?php

namespace App\Payments;

use App\Exceptions\Error;
use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Przelewy24 implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $fields = [
            'sessionId' => (string) $payment->id,
            'merchantId' => config('przelewy24.merchant_id'),
            'amount' => (int) $payment->amount * 100,
            'currency' => $payment->order->currency,
            'crc' => config('przelewy24.crc'),
        ];

        $sign = self::sign($fields);

        $response = Http::withBasicAuth(
            config('przelewy24.pos_id'),
            config('przelewy24.secret_id'),
        )->post(
            config('przelewy24.url') . '/transaction/register',
            $fields + [
                'posId' => config('przelewy24.pos_id'),
                'description' => 'Zakupy w sklepie internetowym.',
                'email' => $payment->order->email,
                'country' => 'PL',
                'language' => 'pl',
                'urlReturn' => $payment->continue_url,
                'urlStatus' => config('app.url') . '/payments/przelewy24',
                'timeLimit' => 0,
                'transferLabel' => 'Zamówienie ' . $payment->order->code,
                'sign' => $sign,
            ],
        );

        if ($response->failed()) {
            throw new Exception($response->json());
        }

        return [
            'redirect_url' => $response['data']['token'],
        ];
    }

    public static function translateNotification(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|number|exists:payments,id',
        ]);

        $payment = Payment::find($request->sesionId)->with('order');

        $amount = (int) $payment->amount * 100;

        $validated = $request->validate([
            'merchantId' => 'required|number|in:' . config('przelewy24.merchant_id'),
            'posId' => 'required|number|in:' . config('przelewy24.pos_id'),
            'sessionId' => 'required|number',
            'amount' => 'required|number|in:' . $amount,
            'originAmount' => 'required|number|in:' . $amount,
            'currency' => 'required|string|in:' . $payment->order->currency,
            'orderId' => 'required|number',
            'methodId' => 'required|number',
            'statement' => 'required|string',
            'sign' => 'required|string',
        ]);

        $sign = self::sign([
            'merchantId' => $validated['merchantId'],
            'posId' => $validated['posId'],
            'sessionId' => $validated['sessionId'],
            'amount' => $validated['amount'],
            'originAmount' => $validated['originAmount'],
            'currency' => $validated['currency'],
            'orderId' => $validated['orderId'],
            'methodId' => $validated['methodId'],
            'statement' => $validated['statement'],
            'crc' => config('przelewy24.crc'),
        ]);

        if ($validated['sign'] != $sign) {
            return Error::abort('Invalid payment!', 400);
        }

        $sign = self::sign([
            'sessionId' => $validated['sessionId'],
            'orderId' => $validated['orderId'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'crc' => config('przelewy24.crc'),
        ]);

        $response = Http::withBasicAuth(
            config('przelewy24.pos_id'),
            config('przelewy24.secret_id'),
        )->post(config('przelewy24.url') . '/transaction/verify', [
            'merchantId' => $validated['merchantId'],
            'posId' => $validated['posId'],
            'sessionId' => $validated['sessionId'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'orderId' => $validated['orderId'],
            'sign' => $sign,
        ]);

        if ($response->failed()) {
            return Error::abort('Cannot verify payment!', 400);
        }

        $payment->update([
            'external_id' => $validated['orderId'],
            'payed' => true,
        ]);
    }

    private static function sign(array $fields): string
    {
        $json = json_encode(
            $fields,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return hash('sha384', $json);
    }
}
