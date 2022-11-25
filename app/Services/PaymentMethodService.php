<?php

namespace App\Services;

use App\Dtos\PaymentMethodIndexDto;
use App\Models\PaymentMethod;
use App\Services\Contracts\PaymentMethodServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PaymentMethodService implements PaymentMethodServiceContract
{
    public function index(PaymentMethodIndexDto $dto): Collection
    {
        $criteria = $dto->toArray();

        if (!Auth::user()->can('payment_methods.show_hidden')) {
            $criteria['public'] = true;
        }

        $query = PaymentMethod::query()->searchByCriteria($criteria);

        return $query->get();
    }
}
