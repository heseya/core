<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Services;

use App\Enums\ExceptionsEnums\Exceptions;
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
        // TODO This is a reminder in case more payments meeting this condition are added in the future,
        // as currently, there is only one such method, and the front displays transfer information for it based on this condition.
        if (PaymentMethod::query()->where('creates_default_payment', '=', true)->where('type', '=', 'prepaid')->count() > 1) {
            throw new ClientException(Exceptions::CLIENT_PAYMENT_METHOD_PREPAID_AND_DEFAULT_PAYMENT);
        }
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
