<?php

namespace App\Services\Contracts;

use App\Dtos\PaymentMethodDto;
use App\Models\PaymentMethod;

interface PaymentMethodServiceContract
{
    public function store(PaymentMethodDto $dto): PaymentMethod;

    public function update(PaymentMethod $paymentMethod, PaymentMethodDto $dto): PaymentMethod;
}
