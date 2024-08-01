<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Traits\GetPublishedLanguageFilter;
use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Dtos\SalesChannelIndexDto;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

final class SalesChannelRepository
{
    use GetPublishedLanguageFilter;

    public function getOne(string $id): SalesChannel
    {
        $query = SalesChannel::where('id', '=', $id)
            ->withCount('organizations');

        if (Gate::denies('sales_channels.show_hidden')) {
            $query->where('status', '!=', SalesChannelStatus::PRIVATE);
            /** @var App|User|null $user */
            $user = Auth::user();
            if ($user) {
                $query->hiddenInOrganization($user);
            }
        }

        return $query->firstOrFail();
    }

    /**
     * @return LengthAwarePaginator<SalesChannel>
     */
    public function getAll(SalesChannelIndexDto $dto): LengthAwarePaginator
    {
        return SalesChannel::searchByCriteria($dto->toArray() + $this->getPublishedLanguageFilter('sales_channels'))
            ->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @return LengthAwarePaginator<SalesChannel>
     */
    public function getAllPublic(SalesChannelIndexDto $dto): LengthAwarePaginator
    {
        $query = SalesChannel::searchByCriteria($dto->toArray() + $this->getPublishedLanguageFilter('sales_channels'))
            ->where('status', '=', SalesChannelStatus::PUBLIC);

        /** @var App|User|null $user */
        $user = Auth::user();
        if ($user) {
            $query->hiddenInOrganization($user);
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    public function getDefault(): SalesChannel
    {
        return SalesChannel::query()->where('default', '=', true)->firstOrFail();
    }

    /**
     * @throws ClientException
     */
    public function store(SalesChannelCreateDto $dto): SalesChannel
    {
        if ($dto->default) {
            SalesChannel::query()->where('default', '=', true)->update(['default' => false]);
        }

        $channel = new SalesChannel($dto->toArray());

        foreach ($dto->translations as $lang => $translation) {
            $channel->setLocale($lang)->fill($translation);
        }

        $channel->save();

        if (is_array($dto->shipping_method_ids) && count($dto->shipping_method_ids) > 0) {
            $channel->shippingMethods()->attach($dto->shipping_method_ids);
        } else {
            $channel->shippingMethods()->attach(ShippingMethod::query()->pluck('id'));
        }

        if (is_array($dto->payment_method_ids) && count($dto->payment_method_ids) > 0) {
            $channel->paymentMethods()->attach($dto->payment_method_ids);
        } else {
            $channel->paymentMethods()->attach(PaymentMethod::query()->pluck('id'));
        }
        $channel->loadCount('organizations');

        return $channel;
    }

    public function update(SalesChannel $channel, SalesChannelUpdateDto $dto): SalesChannel
    {
        if (!$channel->default && $dto->default) {
            SalesChannel::query()->where('default', '=', true)->update(['default' => false]);
        }

        if (is_array($dto->translations)) {
            foreach ($dto->translations as $lang => $translation) {
                $channel->setLocale($lang)->fill($translation);
            }
        }

        if (is_array($dto->shipping_method_ids)) {
            $channel->shippingMethods()->sync($dto->shipping_method_ids);
        }
        if (is_array($dto->payment_method_ids)) {
            $channel->paymentMethods()->sync($dto->payment_method_ids);
        }

        $channel->fill($dto->toArray());
        $channel->save();
        $channel->loadCount('organizations');

        return $channel;
    }

    /**
     * @throws ClientException
     */
    public function delete(SalesChannel $salesChannel): void
    {
        if (SalesChannel::query()->count() <= 1) {
            throw new ClientException(Exceptions::CLIENT_ONE_SALES_CHANNEL_REMAINS);
        }

        if ($salesChannel->default) {
            throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_DEFAULT_DELETE);
        }

        $salesChannel->delete();
    }
}
