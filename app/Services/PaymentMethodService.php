<?php

namespace App\Services;

use App\Dtos\PaymentMethodDto;
use App\Dtos\PaymentMethodIndexDto;
use App\Exceptions\ClientException;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Contracts\PaymentMethodServiceContract;
use Domain\App\Models\App;
use Illuminate\Support\Collection;
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
