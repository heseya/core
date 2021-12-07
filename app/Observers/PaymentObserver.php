<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment)
    {
        if ($payment->paid) {
            $payment->order->update([
                'paid' => $payment->order->isPaid(),
            ]);
        }
    }

    public function updated(Payment $payment)
    {
        if ($payment->paid) {
            $payment->order->update([
                'paid' => $payment->order->isPaid(),
            ]);
        }
    }
}
