<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Traits\GetPublishedLanguageFilter;
use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Dtos\SalesChannelIndexDto;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;
use Support\Enum\Status;

final class SalesChannelRepository
{
    use GetPublishedLanguageFilter;

    public function getOne(string $id): SalesChannel
    {
        $query = SalesChannel::where('id', '=', $id)
            ->with('countries')
            ->firstOrFail();

        if (Gate::denies('sales_channels.show_hidden')) {
            $query->where('status', '!=', Status::INACTIVE->value);
        }

        return $query;
    }

    /**
     * @return LengthAwarePaginator<SalesChannel>
     */
    public function getAll(SalesChannelIndexDto $dto): LengthAwarePaginator
    {
        return SalesChannel::searchByCriteria($dto->toArray() + $this->getPublishedLanguageFilter('sales_channels'))
            ->with('countries')
            ->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @return LengthAwarePaginator<SalesChannel>
     */
    public function getAllPublic(SalesChannelIndexDto $dto): LengthAwarePaginator
    {
        return SalesChannel::searchByCriteria($dto->toArray())
            ->with('countries')
            ->where('status', '=', Status::ACTIVE->value)
            ->paginate(Config::get('pagination.per_page'));
    }

    public function store(SalesChannelCreateDto $dto): SalesChannel
    {
        $channel = new SalesChannel($dto->toArray());

        foreach ($dto->translations as $lang => $translation) {
            $channel->setLocale($lang)->fill($translation);
        }

        $channel->save();
        if (is_array($dto->countries)) {
            $channel->countries()->sync($dto->countries);
        }

        return $channel;
    }

    public function update(string $id, SalesCHannelUpdateDto $dto): void
    {
        /** @var SalesChannel $channel */
        $channel = SalesChannel::query()->where('id', '=', $id)->firstOrFail();

        if (is_array($dto->translations)) {
            foreach ($dto->translations as $lang => $translation) {
                $channel->setLocale($lang)->fill($translation);
            }
        }

        if (is_array($dto->countries)) {
            $channel->countries()->sync($dto->countries);
        }

        $channel->fill($dto->toArray());
        $channel->save();
    }

    /**
     * @throws ClientException
     */
    public function delete(string $id): void
    {
        if (SalesChannel::query()->count() <= 1) {
            throw new ClientException(Exceptions::CLIENT_ONE_SALES_CHANNEL_REMAINS);
        }

        SalesChannel::query()
            ->where('id', '=', $id)
            ->delete();
    }
}
