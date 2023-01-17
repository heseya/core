<?php

namespace App\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Omnipay\Omnipay;
use Omnipay\PayPal\RestGateway;

class PayPal implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        /**
         * @var RestGateway $gateway
         */
        $gateway = Omnipay::create('PayPal_Rest');
        $gateway->setClientId(Config::get('paypal.client_id'));
        $gateway->setSecret(Config::get('paypal.client_secret'));
        $gateway->setTestMode(Config::get('paypal.sandbox'));

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
            'returnUrl' => Config::get('app.url') . '/payments/paypal',
            'cancelUrl' => Config::get('app.url') . '/payments/paypal',
        ])->send();

        return [
            'external_id' => $response->getData()['id'],
            'redirect_url' => $response->getRedirectUrl(),
        ];
    }

    public static function translateNotification(Request $request): mixed
    {
        /**
         * @var RestGateway $gateway
         */
        $gateway = Omnipay::create('PayPal_Rest');
        $gateway->setClientId(Config::get('paypal.client_id'));
        $gateway->setSecret(Config::get('paypal.client_secret'));
        $gateway->setTestMode(Config::get('paypal.sandbox'));

        $transaction = $gateway->completePurchase([
            'payer_id' => $request->input('PayerID'),
            'transactionReference' => $request->input('paymentId'),
        ]);
        $response = $transaction->send();

        $payment = Payment::query()
            ->where('external_id', $request->input('paymentId'))
            ->firstOrFail();

        if (!$response->isSuccessful()) {
            return $response->getMessage();
        }

        $payment->update([
            'status' => PaymentStatus::SUCCESSFUL,
        ]);

        return Redirect::to(Config::get('app.store_url') . '/status/' . $payment->order->code);
    }
}
