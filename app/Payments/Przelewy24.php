<?php

namespace App\Payments;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Przelewy24 implements PaymentMethod
{
    private const API_VER = 'v1';

    public static function generateUrl(Payment $payment): array
    {
        $fields = [
            'sessionId' => (string) $payment->id,
            'merchantId' => (int) Config::get('przelewy24.merchant_id'),
            'amount' => round($payment->amount * 100, 0),
            'currency' => (string) $payment->order->currency,
            'crc' => (string) Config::get('przelewy24.crc'),
        ];

        $sign = self::sign($fields);

        $response = Http::withBasicAuth(
            Config::get('przelewy24.pos_id'),
            Config::get('przelewy24.secret_id'),
        )->post(
            Config::get('przelewy24.url') . '/api/' . self::API_VER . '/transaction/register',
            array_merge($fields, [
                'posId' => Config::get('przelewy24.pos_id'),
                'description' => 'Zamowienie ' . $payment->order->code,
                'email' => $payment->order->email,
                'country' => 'PL',
                'language' => 'pl',
                'urlReturn' => $payment->continue_url,
                'urlStatus' => Config::get('app.url') . '/payments/przelewy24',
                'timeLimit' => 0,
                'transferLabel' => 'Zamowienie ' . $payment->order->code,
                'sign' => $sign,
            ]),
        );

        if ($response->failed()) {
            throw new Exception('Przelewy24 request error');
        }

        return [
            'redirect_url' => Config::get('przelewy24.url') . '/trnRequest/' .
                $response['data']['token'],
        ];
    }

    public static function translateNotification(Request $request): mixed
    {
        Log::info('Received Przelewy24 notification', (array) $request->json());

        ['sessionId' => $sessionId] = $request->validate([
            'sessionId' => ['required', 'string', 'exists:payments,id'],
        ]);

        /** @var Payment $payment */
        $payment = Payment::query()->with('order')->where('id', $sessionId)->firstOr(function () use ($sessionId) {
            Log::error("Przelewy24 - Not found payments with ID: $sessionId");
            throw new ClientException(Exceptions::CLIENT_INVALID_PAYMENT);
        });
        $amount = round($payment->amount * 100, 0);

        $validated = $request->validate([
            'merchantId' => ['required', 'integer', 'in:' . Config::get('przelewy24.merchant_id')],
            'posId' => ['required', 'integer', 'in:' . Config::get('przelewy24.pos_id')],
            'amount' => ['required', 'integer', 'in:' . $amount],
            'originAmount' => ['required', 'integer', 'in:' . $amount],
            'currency' => ['required', 'string', 'in:' . $payment->order->currency],
            'orderId' => ['required', 'integer'],
            'methodId' => ['required', 'integer'],
            'statement' => ['required', 'string'],
            'sign' => ['required', 'string'],
        ]);

        $sign = self::sign([
            'merchantId' => $validated['merchantId'],
            'posId' => $validated['posId'],
            'sessionId' => $sessionId,
            'amount' => $validated['amount'],
            'originAmount' => $validated['originAmount'],
            'currency' => $validated['currency'],
            'orderId' => $validated['orderId'],
            'methodId' => $validated['methodId'],
            'statement' => $validated['statement'],
            'crc' => Config::get('przelewy24.crc'),
        ]);

        if ($validated['sign'] !== $sign) {
            Log::error('Przelewy24 - Payment sign not match');
            throw new ClientException(Exceptions::CLIENT_INVALID_PAYMENT);
        }

        $sign = self::sign([
            'sessionId' => $sessionId,
            'orderId' => $validated['orderId'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'crc' => Config::get('przelewy24.crc'),
        ]);

        $response = Http::withBasicAuth(
            Config::get('przelewy24.pos_id'),
            Config::get('przelewy24.secret_id'),
        )->post(Config::get('przelewy24.url') . '/api/' . self::API_VER . '/transaction/verify', [
            'merchantId' => $validated['merchantId'],
            'posId' => $validated['posId'],
            'sessionId' => $sessionId,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'orderId' => $validated['orderId'],
            'sign' => $sign,
        ]);

        if ($response->failed()) {
            Log::error('Przelewy24 - verification request failed: ' . $response->body());
            throw new ClientException(Exceptions::CLIENT_VERIFY_PAYMENT);
        }

        $payment->update([
            'external_id' => $validated['orderId'],
            'paid' => true,
        ]);

        return null;
    }

    private static function sign(array $fields): string
    {
        $json = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha384', $json);
    }
}
