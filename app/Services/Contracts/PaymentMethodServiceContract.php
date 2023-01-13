<?php

namespace App\Services\Contracts;

use App\Dtos\PaymentMethodDto;
use App\Dtos\PaymentMethodIndexDto;
use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

interface PaymentMethodServiceContract
{
    public function store(PaymentMethodDto $dto): PaymentMethod;

    public function update(PaymentMethod $paymentMethod, PaymentMethodDto $dto): PaymentMethod;

    public function index(PaymentMethodIndexDto $dto): Collection;
}
