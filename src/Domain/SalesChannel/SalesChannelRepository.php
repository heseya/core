<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;

final class SalesChannelRepository
{
    /**
     * @return LengthAwarePaginator<SalesChannel>
     */
    public function getAll(): LengthAwarePaginator
    {
        return SalesChannel::query()
            ->paginate(Config::get('pagination.per_page'));
    }
}
