<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if ($payment->paid) {
            $payment->order->update([
                'paid' => $payment->order->isPaid(),
            ]);
        }
    }

    public function updated(Payment $payment): void
    {
        if ($payment->paid) {
            $payment->order->update([
                'paid' => $payment->order->isPaid(),
            ]);
        }
    }
}
