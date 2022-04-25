<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if ($payment->status->value === PaymentStatus::SUCCESSFUL) {
            $payment->order->update([
                'paid' => $payment->order->isPaid(),
            ]);
        }
    }

    public function updated(Payment $payment): void
    {
        if ($payment->status->value === PaymentStatus::SUCCESSFUL) {
            $payment->order->update([
                'paid' => $payment->order->isPaid(),
            ]);
        }
    }
}
