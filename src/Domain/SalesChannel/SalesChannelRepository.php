<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use Domain\SalesChannel\Dtos\SalesChannelCreateDto;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;

final class SalesChannelRepository
{
    public function getOne(string $id): SalesChannel
    {
        return SalesChannel::where('id', '=', $id)->firstOrFail();
    }

    /**
     * @return LengthAwarePaginator<SalesChannel>
     */
    public function getAll(): LengthAwarePaginator
    {
        return SalesChannel::query()
            ->paginate(Config::get('pagination.per_page'));
    }

    public function store(SalesChannelCreateDto $dto): SalesChannel
    {
        return SalesChannel::create($dto->toArray());
    }

    public function update(string $id, SalesCHannelUpdateDto $dto): void
    {
        SalesChannel::query()
            ->where('id', '=', $id)
            ->update($dto->toArray());
    }

    public function delete(string $id): void
    {
        SalesChannel::query()
            ->where('id', '=', $id)
            ->delete();
    }
}
