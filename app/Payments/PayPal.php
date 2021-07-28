<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Omnipay\Omnipay;

class PayPal implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $gateway = Omnipay::create('PayPal_Rest');
        $gateway->setClientId(config('paypal.client_id'));
        $gateway->setSecret(config('paypal.client_secret'));
        $gateway->setTestMode(config('paypal.sandbox'));

        $response = $gateway->purchase([
            'amount' => $payment->amount,
            'items' => [
                [
                    'name' => $payment->order->code,
                    'price' => $payment->amount,
                    'description' => 'Order ' . $payment->order->code,
                    'quantity' => 1,
                ],
            ],
            'currency' => $payment->order->currency,
            'returnUrl' => config('app.url') . '/payments/paypal',
            'cancelUrl' => config('app.url') . '/payments/paypal',
        ])->send();

        return [
            'external_id' => $response->getData()['id'],
            'redirect_url' => $response->getRedirectUrl(),
        ];
    }

    public static function translateNotification(Request $request): mixed
    {
        $gateway = Omnipay::create('PayPal_Rest');
        $gateway->setClientId(config('paypal.client_id'));
        $gateway->setSecret(config('paypal.client_secret'));
        $gateway->setTestMode(config('paypal.sandbox'));

        $transaction = $gateway->completePurchase([
            'payer_id' => $request->input('PayerID'),
            'transactionReference' => $request->input('paymentId'),
        ]);
        $response = $transaction->send();

        $payment = Payment::where('external_id', $request->input('paymentId'))->firstOrFail();

        if (!$response->isSuccessful()) {
            return $response->getMessage();
        }

        $payment->update([
            'payed' => true,
        ]);

        return redirect(config('app.store_url') . '/status/' . $payment->order->code);
    }
}
