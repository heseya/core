<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Services;

use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\User;
use Domain\PaymentMethods\Dtos\PaymentMethodCreateDto;
use Domain\PaymentMethods\Dtos\PaymentMethodIndexDto;
use Domain\PaymentMethods\Dtos\PaymentMethodUpdateDto;
use Domain\PaymentMethods\Models\PaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class PaymentMethodService
{
    /**
     * @throws ClientException
     */
    public function store(PaymentMethodCreateDto $dto): PaymentMethod
    {
        return PaymentMethod::create($dto->toArray() + ['app_id' => Auth::id()]);
    }

    public function update(PaymentMethod $paymentMethod, PaymentMethodUpdateDto $dto): PaymentMethod
    {
        $paymentMethod->update($dto->toArray());

        return $paymentMethod;
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
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
