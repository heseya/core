<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Paynow\Client;
use Paynow\Environment;

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
            'amount' => (int) ($payment->amount * 100),
            'currency' => $payment->order->currency,
            'externalId' => $payment->order->code,
            'description' => 'Zakupy w sklepie internetowym.',
            'buyer' => [
                'email' => $payment->order->email,
            ],
        ];

        if ($payment->continueUrl !== null) {
            $paymentData['continueUrl'] = $payment->continueUrl;
        }

        $response = new \Paynow\Service\Payment($client);
        $result = $response->authorize($paymentData, $idempotencyKey);

        return [
            'redirect_url' => $result->redirectUrl,
            'external_id' => $result->paymentId,
        ];
    }

    public static function translateNotification(Request $request): JsonResponse
    {
        $payment = Payment::findOrFail($request->input('paymentId'));

        if ($request->input('status') === 'CONFIRMED') {
            $payment->update([
                'payed' => true,
            ]);
        }

        return response()->json(null);
    }
}
