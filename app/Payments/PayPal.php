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
    private const APPROVAL_URL = 'approval_url';

    public static function generateUrl(Payment $payment): array
    {
        /**
         * @var RestGateway $gateway
         */
        $gateway = Omnipay::create('PayPal_Rest');
        $gateway->setClientId(Config::get('paypal.client_id'));
        $gateway->setSecret(Config::get('paypal.client_secret'));
        $gateway->setTestMode(Config::get('paypal.sandbox'));

        $amount = $payment->amount->getAmount()->toFloat();

        $response = $gateway->purchase([
            'amount' => $amount,
            'items' => [
                [
                    'name' => $payment->order->code,
                    'price' => $amount,
                    'description' => 'Order ' . $payment->order->code,
                    'quantity' => 1,
                ],
            ],
            'currency' => $payment->currency->value,
            'returnUrl' => Config::get('app.url') . '/payments/paypal',
            'cancelUrl' => Config::get('app.url') . '/payments/paypal',
        ])->send();

        return [
            'external_id' => $response->getData()['id'],
            'redirect_url' => $response->getRedirectUrl(),
            'additional_data' => PayPal::getTokenTransaction($response->getData()),
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

        $validated = $request->validate([
            'PayerID' => ['sometimes', 'required', 'string'],
            'paymentId' => ['required_with:PayerID', 'string'],
            'token' => ['sometimes', 'required', 'string'],
        ]);

        if (array_key_exists('token', $validated)
            && !array_key_exists('PayerID', $validated)
            && !array_key_exists('paymentId', $validated)) {
            $payment = Payment::query()
                ->where('additional_data', $validated['token'])
                ->firstOrFail();

            return Redirect::to($payment->continue_url ?? Config::get('app.store_url'));
        }

        $transaction = $gateway->completePurchase([
            'payer_id' => $validated['PayerID'],
            'transactionReference' => $validated['paymentId'],
        ]);
        $response = $transaction->send();

        $payment = Payment::query()
            ->where('external_id', $validated['paymentId'])
            ->firstOrFail();

        if (!$response->isSuccessful()) {
            return $response->getMessage();
        }

        $payment->update([
            'status' => PaymentStatus::SUCCESSFUL,
        ]);

        return Redirect::to($payment->continue_url ?? Config::get('app.store_url'));
    }

    private static function getUrlQueryVariables(string $variablesString): array
    {
        $variables = explode('&', $variablesString);
        $result = [];

        foreach ($variables as $variable)
        {
            $explode = explode('=', $variable);
            $result[$explode[0]] = $explode[1];
        }

        return $result;
    }

    private static function getTokenTransaction(array $data): ?string
    {
        if (!array_key_exists('links', $data)) {
            return null;
        }
        $token = null;

        foreach ($data['links'] as $link) {
            if (!array_key_exists('href', $link) && !array_key_exists('rel', $link)) {
                continue;
            }

            if ($link['rel'] !== PayPal::APPROVAL_URL) {
                continue;
            }

            $urlQueryString = parse_url($link['href'], PHP_URL_QUERY);

            if (!is_string($urlQueryString)) {
                return null;
            }

            $queryVariables = PayPal::getUrlQueryVariables($urlQueryString);

            if (!array_key_exists('token', $queryVariables)) {
                return null;
            }

            $token = $queryVariables['token'];
            break;
        }

        return $token;
    }
}
