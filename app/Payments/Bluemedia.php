<?php

namespace App\Payments;

use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use SimpleXMLElement;

class Bluemedia implements PaymentMethod
{
    public static function generateUrl(Payment $payment): array
    {
        $amount = number_format($payment->amount, 2, '.', '');

        $hash = hash(config('bluemedia.hash'),
            config('bluemedia.service_id') . '|' .
            $payment->id . '|' .
            $amount . '|' .
            config('bluemedia.key'),
        );

        $url = 'https://pay.bm.pl/payment' .
            '?ServiceID=' . config('bluemedia.service_id') .
            '&OrderID=' . $payment->id .
            '&Amount=' . $amount .
            '&Hash=' . $hash;

        return [
            'redirect_url' => $url,
        ];
    }

    public static function translateNotification(Request $request)
    {
        try {
            $itn = new SimpleXMLElement(base64_decode($request->getContent()));
        } catch (Exception $e) {
            return self::response('XXX', 'NOTCONFIRMED');
        }

        // dd($itn);

        $payment_id = $itn->transactions->transaction->orderID;

        // niepoprawny Service ID
        if ($itn->serviceID != config('bluemedia.service_id')) {
            return self::response($payment_id, 'NOTCONFIRMED');
        }

        $payment = Payment::where('id', $payment_id);

        // nieznaleziono płatności o danym id
        if ($payment) {
            return self::response($payment_id, 'NOTCONFIRMED');
        }

        // złe dane w komunukacie z BM
        if (
            $itn->transactions->transaction->amount != $payment->amount &&
            $itn->transactions->transaction->currency !== $payment->currency
        ) {
            return self::response($payment_id, 'NOTCONFIRMED');
        }

        $status = $itn->transactions->transaction->paymentStatus;

        if ($status === 'PENDING') {
            $payment->update([
                'status' => Payment::STATUS_PENDING
            ]);
        } elseif ($status === 'SUCCESS') {
            $payment->update([
                'status' => Payment::STATUS_PAYED
            ]);
        } elseif ($status === 'FAILURE') {
            $payment->update([
                'status' => Payment::STATUS_FAILURE
            ]);
        } else {
            return self::response($payment_id, 'NOTCONFIRMED');
        }

        return self::response($payment_id, 'CONFIRMED');
    }

    private static function response(string $payment_id, string $confirmation)
    {
        $hash = hash(config('bluemedia.hash'),
            config('bluemedia.service_id') . '|' .
            $payment_id . '|' .
            $confirmation . '|' .
            config('bluemedia.key'),
        );

        return response(
            '<?xml version="1.0"?>
            <confirmationList>
                <serviceID>' . config('bluemedia.service_id') . '</serviceID>
                    <transactionsConfirmations>
                        <transactionConfirmed>
                        <orderID>' . $payment_id . '</orderID>
                        <confirmation>' . $confirmation . '</confirmation>
                        </transactionConfirmed>
                    </transactionsConfirmations>
                <hash>' . $hash . '</hash>
            </confirmationList>',
            200,
            ['Content-Type' => 'application/xml'],
        );
    }
}
