<?php

namespace App\Observers;

use App\Events\OrderUpdatedPaid;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $this->process($payment);
    }

    public function updated(Payment $payment): void
    {
        $this->process($payment);
    }

    private function process(Payment $payment): void
    {
        $isPaid = $payment->order?->isPaid();

        // update only if paid status changed
        if ($payment->order && $payment->order->paid !== $isPaid) {
            $payment->order->update([
                'paid' => $isPaid,
            ]);

            OrderUpdatedPaid::dispatch($payment->order);
        }
    }
}
