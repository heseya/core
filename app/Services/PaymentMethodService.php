<?php

namespace App\Services;

use App\Dtos\PaymentMethodDto;
use App\Exceptions\ClientException;
use App\Models\PaymentMethod;
use App\Services\Contracts\PaymentMethodServiceContract;
use Illuminate\Support\Facades\Auth;

class PaymentMethodService implements PaymentMethodServiceContract
{
    /**
     * @throws ClientException
     */
    public function store(PaymentMethodDto $dto): PaymentMethod
    {
        return PaymentMethod::create($dto->toArray() + ['app_id' => Auth::id()]);
    }

    public function update(PaymentMethod $paymentMethod, PaymentMethodDto $dto): PaymentMethod
    {
        $paymentMethod->update($dto->toArray());

        return $paymentMethod;
    }
}
