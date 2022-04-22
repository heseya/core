<?php

namespace App\Services;

use App\Dtos\PaymentMethodDto;
use App\Models\PaymentMethod;

use App\Services\Contracts\PaymentMethodServiceContract;

class PaymentMethodService implements PaymentMethodServiceContract
{
    public function store(PaymentMethodDto $dto): PaymentMethod
    {
        return PaymentMethod::create($dto->toArray());
    }
}
