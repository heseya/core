<?php

namespace App\Services;

use App\Dtos\PaymentMethodIndexDto;
use App\Models\App;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Contracts\PaymentMethodServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PaymentMethodService implements PaymentMethodServiceContract
{
    public function index(PaymentMethodIndexDto $dto): Collection
    {
        $criteria = $dto->toArray();

        /** @var User|App $user */
        $user = Auth::user();

        if (!$user->can('payment_methods.show_hidden')) {
            $criteria['public'] = true;
        }

        $query = PaymentMethod::query()->searchByCriteria($criteria);

        return $query->get();
    }
}
