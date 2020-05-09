<?php

namespace App\Payments;

use App\Payment;
use Paynow\Client;
use Paynow\Environment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayNow implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $client = new Client(
            config('paynow.api_key'),
            config('paynow.signature_key'),
            Environment::SANDBOX,
        );
        $orderReference = $payment->id;
        $idempotencyKey = uniqid($orderReference . '_');

        $paymentData = [
            'amount' => $payment->amount * 100,
            'currency' => 'PLN',
            'externalId' => $payment->order->code,
            'description' => 'Zakupy w sklepie internetowym.',
            'buyer' => [
                'email' => $payment->order->email,
            ],
        ];

        if ($payment->continueUrl !== null) {
            $paymentData['continueUrl'] = $payment->continueUrl;
        }

        try {
            $response = new \Paynow\Service\Payment($client);
            $result = $response->authorize($paymentData, $idempotencyKey);
        } catch (PaynowException $exception) {
            return false;
        }

        return [
            'redirect_url' => $result->redirectUrl,
            'external_id' => $result->paymentId,
            'status' => $result->status === 'NEW' ? Payment::STATUS_PENDING : null,
        ];
    }

    public static function translateNotification(Request $request): array
    {
        return [
            'status' => ''
        ];
    }
}
