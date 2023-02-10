<?php

namespace App\Payments;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\PaymentStatus;
use App\Exceptions\ClientException;
use App\Models\Payment;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

class PayU implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $client_id = Config::get('payu.client_id');
        $client_secret = Config::get('payu.client_secret');

        $payuUrl = rtrim(Config::get('payu.url'), '/');
        $appUrl = rtrim(Config::get('app.url'), '/');

        $response = Http::post(
            $payuUrl . '/pl/standard/user/oauth/authorize?grant_type=client_credentials&client_id=' .
                $client_id . '&client_secret=' . $client_secret,
        )->throw();

        $amount = (int) ($payment->amount * 100);

        $response = Http::withToken($response['access_token'])->withOptions([
            'allow_redirects' => false,
        ])->post($payuUrl . '/api/v2_1/orders', [
            'notifyUrl' => $appUrl . '/payments/payu',
            'customerIp' => '127.0.0.1',
            'merchantPosId' => Config::get('payu.pos_id'),
            'description' => 'Zamowienie nr ' . $payment->order->code,
            'currencyCode' => $payment->order->currency,
            'totalAmount' => $amount,
            'extOrderId' => $payment->getKey(),
            'continueUrl' => $payment->continue_url,
            'buyer' => [
                'email' => $payment->order->email,
            ],
            'products' => [
                [
                    'name' => 'Zamowienie nr ' . $payment->order->code,
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

    public static function translateNotification(Request $request): JsonResponse
    {
        $signature = self::parseSignature($request->header('OpenPayu-Signature'));

        if (!self::verifySignature(
            $request->getContent(),
            $signature['signature'],
            Config::get('payu.second_key'),
            $signature['algorithm']
        )) {
            throw new ClientException(Exceptions::CLIENT_UNTRUSTED_NOTIFICATION);
        }

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
                'status' => PaymentStatus::SUCCESSFUL,
            ]);
        }

        return Response::json(null);
    }

    /**
     * Function returns signature data object
     *
     * @param string $data
     *
     * @return array|null
     */
    public static function parseSignature(string $data)
    {
        $signatureData = [];

        $list = explode(';', rtrim($data, ';'));
        if (!count($list)) {
            return;
        }

        foreach ($list as $value) {
            $explode = explode('=', $value);
            if (count($explode) !== 2) {
                return;
            }
            $signatureData[$explode[0]] = $explode[1];
        }

        return $signatureData;
    }

    /**
     * Function returns signature validate
     */
    public static function verifySignature(
        mixed $message,
        mixed $signature,
        mixed $signatureKey,
        string $algorithm = 'MD5',
    ): bool {
        if (isset($signature)) {
            if ($algorithm === 'MD5') {
                $hash = md5($message . $signatureKey);
            } elseif (in_array($algorithm, ['SHA', 'SHA1', 'SHA-1'])) {
                $hash = sha1($message . $signatureKey);
            } else {
                $hash = hash('sha256', $message . $signatureKey);
            }

            if (strcmp($signature, $hash) === 0) {
                return true;
            }
        }

        return false;
    }
}
