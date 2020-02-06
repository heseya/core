<?php

namespace App\Payment;

trait Payable
{
    public $continueUrl = 'https://canary.depth.store/done';

    function paymentLink(): string
    {
        $data = [
            'continueUrl' => $this->continueUrl,
            'code' => $this->code,
            'email' => $this->email,
            'amount' => 220,
        ];

        return PayNow::generateUrl($data);
    }
}
