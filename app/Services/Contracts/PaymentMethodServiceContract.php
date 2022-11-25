<?php

namespace App\Services\Contracts;

use App\Dtos\PaymentMethodIndexDto;
use Illuminate\Support\Collection;

interface PaymentMethodServiceContract
{
    public function index(PaymentMethodIndexDto $dto): Collection;
}
