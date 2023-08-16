<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Dtos\SalesChannelIndexDto;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Support\Enum\Status;

final class SalesChannelRepository
{
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
        return SalesChannel::searchByCriteria($dto->toArray())
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
        $channel->countries()->sync($dto->countries);

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

    public function delete(string $id): void
    {
        SalesChannel::query()
            ->where('id', '=', $id)
            ->delete();
    }
}
