<?php

namespace App\Payments;

use App\Exceptions\StoreException;
use App\Models\Payment;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class PayU implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $client_id = config('payu.client_id');
        $client_secret = config('payu.client_secret');

        $response = Http::post(
            config('payu.url') . '/pl/standard/user/oauth/authorize?grant_type=client_credentials&client_id=' .
                $client_id . '&client_secret=' . $client_secret,
        )->throw();

        $amount = (int) ($payment->amount * 100);

        $response = Http::withToken($response['access_token'])->withOptions([
            'allow_redirects' => false,
        ])->post(config('payu.url') . '/api/v2_1/orders', [
            'notifyUrl' => config('app.url') . '/payments/payu',
            'customerIp' => '127.0.0.1',
            'merchantPosId' => config('payu.pos_id'),
            'description' => 'Zakupy w sklepie internetowym.',
            'currencyCode' => $payment->order->currency,
            'totalAmount' => $amount,
            'extOrderId' => $payment->getKey(),
            'returnUrl' => config('app.store_url') . '/status/' . $payment->order->code,
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

    public static function translateNotification(Request $request): JsonResponse
    {
        $signature = self::parseSignature($request->header('OpenPayu-Signature'));

        if (!self::verifySignature(
            $request->getContent(),
            $signature['signature'],
            Config::get('payu.second_key'),
            $signature['algorithm']
        )) {
            throw new StoreException('Untrusted notification');
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
                'payed' => true,
            ]);
        }

        return response()->json(null);
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
            return null;
        }

        foreach ($list as $value) {
            $explode = explode('=', $value);
            if (count($explode) !== 2) {
                return null;
            }
            $signatureData[$explode[0]] = $explode[1];
        }

        return $signatureData;
    }

    /**
     * Function returns signature validate
     *
     * @param string $message
     * @param string $signature
     * @param string $signatureKey
     * @param string $algorithm
     *
     * @return bool
     */
    public static function verifySignature($message, $signature, $signatureKey, $algorithm = 'MD5')
    {
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
